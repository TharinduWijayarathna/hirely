<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class JobApplicationService
{
    /**
     * @return array{application: JobApplication, interview: null}
     */
    public function apply(User $seeker, Job $job, ?string $coverLetter = null): array
    {
        if (! $job->isPubliclyListed()) {
            throw ValidationException::withMessages([
                'job_id' => 'This job is not open for applications.',
            ]);
        }

        $existing = JobApplication::where('user_id', $seeker->id)
            ->where('job_id', $job->id)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'job_id' => 'You have already applied for this job.',
            ]);
        }

        $application = JobApplication::create([
            'user_id' => $seeker->id,
            'job_id' => $job->id,
            'cover_letter' => $coverLetter,
            'applied_at' => now(),
            'status' => 'pending',
            'cv_document_id' => $seeker->latestProcessedCv?->id,
            'resume_path' => $seeker->latestProcessedCv?->path,
        ]);

        app(RecruitmentNotifier::class)->applicationSubmitted($application->load(['job', 'user']));

        // Interview assignment is handled manually by HR via Review Candidates.
        // Applications start in 'pending' status and wait for HR review.
        return ['application' => $application, 'interview' => null];
    }
}
