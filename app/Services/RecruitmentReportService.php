<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Collection;

class RecruitmentReportService
{
    /**
     * @return array<string, mixed>
     */
    public function forHr(User $user, ?int $jobId = null): array
    {
        $jobs = Job::visibleTo($user)->orderBy('title')->get(['id', 'title', 'status']);
        $job = null;

        if ($jobId) {
            $job = Job::visibleTo($user)->findOrFail($jobId);
            $jobIds = collect([$job->id]);
        } else {
            $jobIds = $jobs->pluck('id');
        }

        $applications = JobApplication::whereIn('job_id', $jobIds)->get();
        $interviews = Interview::whereIn('job_id', $jobIds)->get();

        $funnelCounts = $applications->countBy('status');

        return [
            'jobs' => $jobs,
            'selected_job_id' => $job?->id,
            'funnel' => app(DashboardService::class)->funnelPayload($funnelCounts),
            'time_in_stage' => $this->timeInStage($applications),
            'interview_volume' => $this->interviewVolume($interviews),
            'score_distribution' => [
                'interview' => $this->distribution(
                    $interviews
                        ->filter(fn (Interview $interview) => $interview->isUsableForRanking())
                        ->map(fn (Interview $interview) => (float) $interview->score)
                ),
                'ranking' => $this->distribution(
                    $applications
                        ->filter(fn (JobApplication $application) => $application->ranking_score !== null)
                        ->map(fn (JobApplication $application) => (float) $application->ranking_score)
                ),
            ],
        ];
    }

    /**
     * @param  Collection<int, JobApplication>  $applications
     * @return array<int, array{status: string, count: int, avg_days: float|null}>
     */
    protected function timeInStage(Collection $applications): array
    {
        $order = ['pending', 'reviewing', 'shortlisted', 'interviewed', 'accepted', 'rejected'];

        return array_map(function (string $status) use ($applications) {
            $group = $applications->where('status', $status);
            $days = $group
                ->map(fn (JobApplication $application) => $application->applied_at
                    ? round($application->applied_at->diffInDays(now()), 1)
                    : null)
                ->filter(fn ($day) => $day !== null);

            return [
                'status' => $status,
                'count' => $group->count(),
                'avg_days' => $days->isEmpty() ? null : round($days->avg(), 1),
            ];
        }, $order);
    }

    /**
     * @param  Collection<int, Interview>  $interviews
     * @return array<string, mixed>
     */
    protected function interviewVolume(Collection $interviews): array
    {
        $completed = $interviews->where('status', 'completed');
        $usable = $completed->filter(fn (Interview $interview) => $interview->isUsableForRanking());

        return [
            'assigned' => $interviews->count(),
            'completed' => $completed->count(),
            'pending_review' => $completed->where('review_status', Interview::REVIEW_PENDING)->count(),
            'this_month' => $completed
                ->filter(fn (Interview $interview) => $interview->completed_at?->isCurrentMonth())
                ->count(),
            'avg_duration_minutes' => round((float) $completed->avg('duration_minutes'), 1) ?: null,
            'avg_score' => $usable->isEmpty() ? null : round((float) $usable->avg(fn (Interview $interview) => (float) $interview->score), 1),
        ];
    }

    /**
     * @param  Collection<int, float>  $scores
     * @return array<int, array{label: string, count: int}>
     */
    protected function distribution(Collection $scores): array
    {
        $buckets = [
            '0-19' => 0,
            '20-39' => 0,
            '40-59' => 0,
            '60-79' => 0,
            '80-100' => 0,
        ];

        foreach ($scores as $score) {
            $bucket = match (true) {
                $score < 20 => '0-19',
                $score < 40 => '20-39',
                $score < 60 => '40-59',
                $score < 80 => '60-79',
                default => '80-100',
            };
            $buckets[$bucket]++;
        }

        return collect($buckets)
            ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
            ->values()
            ->all();
    }
}
