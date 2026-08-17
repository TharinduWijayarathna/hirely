<?php

namespace App\Services;

use App\Models\AtsAnalysis;
use App\Models\CvDocument;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CvAnalysisService
{
    public function __construct(protected AIService $ai) {}

    public function storeAndAnalyze(User $user, UploadedFile $file): CvDocument
    {
        $disk = (string) config('filesystems.cv', 'local');
        $source = $file->getRealPath() ?: $file->getPathname();
        $contents = is_string($source) && is_readable($source) ? file_get_contents($source) : false;
        $path = $file->store("cvs/{$user->id}", $disk);

        $document = CvDocument::create([
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => 'pending',
        ]);

        try {
            if ($contents === false || $contents === '') {
                throw new \RuntimeException('Could not read the uploaded CV.');
            }

            $analysis = $this->ai->analyzeCurriculumVitae($contents, (string) $document->mime_type);

            $document->update([
                'parsed_text' => $analysis['review']['summary'] ?? null,
                'extraction' => $analysis['extraction'],
                'review' => $analysis['review'],
                'review_score' => $analysis['review']['score'] ?? null,
                'status' => 'processed',
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            Log::error('CV processing failed: '.$e->getMessage(), ['cv_document_id' => $document->id]);

            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $document->fresh();
    }

    public function scoreAgainstJob(User $user, string $jobDescription, ?int $jobId = null, ?int $cvDocumentId = null): AtsAnalysis
    {
        $cv = $this->currentCvFromReview($user, $cvDocumentId);

        if (! $cv) {
            throw new \RuntimeException('Upload and process a CV in CV Review before running ATS scoring.');
        }

        if ($jobId) {
            $job = Job::with('company')->findOrFail($jobId);
            $skills = is_array($job->skills) ? implode(', ', $job->skills) : '';
            $jobDescription = trim(implode("\n", array_filter([
                $job->title,
                $job->company?->name ? 'Company: '.$job->company->name : null,
                $job->description,
                $job->requirements,
                $skills !== '' ? 'Skills: '.$skills : null,
            ])));
        }

        $fileContents = null;
        if ($cv->path && Storage::disk($cv->disk)->exists($cv->path)) {
            $fileContents = Storage::disk($cv->disk)->get($cv->path);
        }

        $result = $this->ai->scoreAtsCompatibility(
            $jobDescription,
            $cv->extraction,
            $fileContents,
            $cv->mime_type,
        );

        return AtsAnalysis::create([
            'user_id' => $user->id,
            'cv_document_id' => $cv->id,
            'job_id' => $jobId,
            'job_description' => $jobDescription,
            'score' => $result['score'],
            'analysis' => $result['analysis'],
        ]);
    }

    public function delete(CvDocument $document): void
    {
        Storage::disk($document->disk)->delete($document->path);
        $document->delete();
    }

    protected function currentCvFromReview(User $user, ?int $cvDocumentId): ?CvDocument
    {
        $query = CvDocument::query()
            ->where('user_id', $user->id)
            ->where('status', 'processed');

        if ($cvDocumentId) {
            return $query->whereKey($cvDocumentId)->first();
        }

        return $user->latestProcessedCv;
    }
}
