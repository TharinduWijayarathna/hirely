<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\AtsAnalysis;
use App\Models\CvDocument;
use App\Models\Job;
use App\Services\CvAnalysisService;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AtsScoringController extends Controller
{
    public function index(Request $request, PlanLimitService $limits): Response
    {
        $user = Auth::user();
        $processedCvs = CvDocument::where('user_id', $user->id)
            ->where('status', 'processed')
            ->latest()
            ->get();

        $selectedId = $request->integer('cv');
        $cv = $processedCvs->firstWhere('id', $selectedId) ?? $user->latestProcessedCv;

        $analyses = AtsAnalysis::where('user_id', $user->id)
            ->with(['job:id,title', 'cvDocument:id,original_name'])
            ->latest()
            ->take(10)
            ->get();

        $jobs = Job::publiclyListed()
            ->with('company:id,name')
            ->orderBy('title')
            ->limit(50)
            ->get()
            ->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'location' => $job->location,
                'type' => $job->type,
                'remote' => $job->remote,
                'skills' => $job->skills ?? [],
                'company' => $job->company?->name,
                'preview' => Str::limit(strip_tags((string) $job->description), 280),
            ]);

        return Inertia::render('job-seeker/ATSScoring', [
            'cv' => $this->cvPayload($cv),
            'cvs' => $processedCvs->map(fn (CvDocument $document) => $this->cvPayload($document))->values(),
            'jobs' => $jobs,
            'analyses' => $analyses,
            'quota' => $limits->quota($user, 'ats'),
        ]);
    }

    public function store(Request $request, CvAnalysisService $analysis, PlanLimitService $limits)
    {
        if ($message = $limits->denyMessage($request->user(), 'ats')) {
            return redirect()->route('ats-scoring')->withErrors(['plan' => $message]);
        }

        $validated = $request->validate([
            'cv_document_id' => [
                'nullable',
                'integer',
                Rule::exists('cv_documents', 'id')->where(
                    fn ($query) => $query
                        ->where('user_id', $request->user()->id)
                        ->where('status', 'processed')
                ),
            ],
            'job_id' => [
                'nullable',
                'integer',
                Rule::exists('job_postings', 'id')->where(fn ($query) => $query->where('status', 'active')),
            ],
            'job_description' => 'required_without:job_id|nullable|string|min:40|max:15000',
        ]);

        try {
            $analysis->scoreAgainstJob(
                $request->user(),
                $validated['job_description'] ?? '',
                $validated['job_id'] ?? null,
                $validated['cv_document_id'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return redirect()->route('ats-scoring')->withErrors(['cv' => $e->getMessage()]);
        }

        return redirect()->route('ats-scoring')->with('success', 'ATS compatibility scored.');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function cvPayload(?CvDocument $cv): ?array
    {
        if (! $cv) {
            return null;
        }

        return [
            'id' => $cv->id,
            'original_name' => $cv->original_name,
            'review_score' => $cv->review_score,
            'status' => $cv->status,
            'created_at' => $cv->created_at,
            'extraction' => [
                'full_name' => $cv->extraction['full_name'] ?? null,
                'summary' => $cv->extraction['summary'] ?? null,
                'skills' => $cv->extraction['skills'] ?? [],
                'technologies' => $cv->extraction['technologies'] ?? [],
                'experience_years' => $cv->extraction['experience_years'] ?? null,
                'experience_level' => $cv->extraction['experience_level'] ?? null,
            ],
            'review' => [
                'summary' => $cv->review['summary'] ?? null,
            ],
        ];
    }
}
