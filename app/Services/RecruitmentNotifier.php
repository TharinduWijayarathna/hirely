<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Notifications\RecruitmentNotification;
use Illuminate\Support\Collection;

class RecruitmentNotifier
{
    public function applicationSubmitted(JobApplication $application): void
    {
        $application->loadMissing(['job', 'user']);
        $jobTitle = $application->job?->title ?? 'a job';
        $candidate = $application->user?->name ?? 'A candidate';

        if ($application->user) {
            $this->notify(
                $application->user,
                'application_received',
                'Application received',
                "Your application for {$jobTitle} has been submitted.",
                '/job-applications',
            );
        }

        $this->notifyHr(
            $application->job,
            'application_submitted',
            'New application',
            "{$candidate} applied for {$jobTitle}.",
            '/review-candidates',
        );
    }

    public function applicationStatusChanged(JobApplication $application): void
    {
        $application->loadMissing(['job', 'user']);

        if (! $application->user) {
            return;
        }

        $jobTitle = $application->job?->title ?? 'a job';
        $status = str_replace('_', ' ', $application->status);

        $this->notify(
            $application->user,
            'application_status',
            'Application update',
            "Your application for {$jobTitle} is now {$status}.",
            '/job-applications',
        );
    }

    public function interviewAssigned(Interview $interview): void
    {
        $interview->loadMissing(['job', 'candidate']);

        if (! $interview->candidate) {
            return;
        }

        $jobTitle = $interview->job?->title ?? 'a role';

        $this->notify(
            $interview->candidate,
            'interview_assigned',
            'Interview assigned',
            "You have a new interview for {$jobTitle}.",
            '/interviews/'.$interview->id,
        );
    }

    public function interviewCompleted(Interview $interview): void
    {
        $interview->loadMissing(['job', 'candidate']);
        $jobTitle = $interview->job?->title ?? 'a role';
        $candidate = $interview->candidate?->name ?? 'A candidate';

        $this->notifyHr(
            $interview->job,
            'interview_completed',
            'Interview ready for review',
            "{$candidate} completed the interview for {$jobTitle}. Review the AI score.",
            '/interview-results/'.$interview->id,
        );
    }

    public function rankingReady(Job $job): void
    {
        $this->notifyHr(
            $job,
            'ranking_ready',
            'Ranking updated',
            "Candidate ranking for {$job->title} has been refreshed.",
            '/rankings?job_id='.$job->id,
        );
    }

    public function interviewReviewed(Interview $interview): void
    {
        $interview->loadMissing(['job', 'candidate']);

        if (! $interview->candidate) {
            return;
        }

        $jobTitle = $interview->job?->title ?? 'a role';
        $status = str_replace('_', ' ', (string) $interview->review_status);

        $this->notify(
            $interview->candidate,
            'interview_reviewed',
            'Interview reviewed',
            "HR {$status} your interview for {$jobTitle}.",
            '/interviews/'.$interview->id,
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function hrRecipients(?Job $job): Collection
    {
        if (! $job) {
            return collect();
        }

        $query = User::query()->where('role', User::ROLE_HR_PROFESSIONAL);

        if ($job->company_id) {
            $query->where('company_id', $job->company_id);
        } else {
            $query->where('id', $job->user_id);
        }

        return $query->get();
    }

    protected function notifyHr(?Job $job, string $type, string $title, string $body, string $url): void
    {
        foreach ($this->hrRecipients($job) as $hr) {
            $this->notify($hr, $type, $title, $body, $url);
        }
    }

    protected function notify(User $user, string $type, string $title, string $body, string $url): void
    {
        $user->notify(new RecruitmentNotification($type, $title, $body, $url));
    }
}
