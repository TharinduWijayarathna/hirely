<?php

namespace App\Services;

use App\Models\AtsAnalysis;
use App\Models\Company;
use App\Models\CvDocument;
use App\Models\Interview;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\MockInterviewSession;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        return match (true) {
            $user->isJobSeeker() => $this->jobSeeker($user),
            $user->isHrProfessional() => $this->hr($user),
            default => $this->admin(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function jobSeeker(User $user): array
    {
        $cv = $user->latestProcessedCv;
        $applications = JobApplication::where('user_id', $user->id);
        $recruitmentInterviews = Interview::where('candidate_id', $user->id);

        $statusCounts = (clone $applications)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $stats = [
            'cv_reviews' => CvDocument::where('user_id', $user->id)->where('status', 'processed')->count(),
            'ats_scores' => AtsAnalysis::where('user_id', $user->id)->count(),
            'interviews_completed' => (clone $recruitmentInterviews)->where('status', 'completed')->count(),
            'interviews_open' => (clone $recruitmentInterviews)->whereIn('status', ['pending', 'in_progress'])->count(),
            'mock_interviews' => MockInterviewSession::where('user_id', $user->id)->where('status', 'completed')->count(),
            'applications' => (clone $applications)->count(),
            'applications_active' => (clone $applications)->whereNotIn('status', ['accepted', 'rejected'])->count(),
            'profile_score' => $this->profileScore($user),
            'cv_score' => $cv?->review_score,
        ];

        return [
            'role' => User::ROLE_JOB_SEEKER,
            'stats' => $stats,
            'charges' => $this->chargePayload(Payment::query()->where('user_id', $user->id)),
            'charts' => [
                'applications' => $this->monthlySeries((clone $applications), 'applied_at'),
                'interviews' => $this->monthlySeries((clone $recruitmentInterviews), 'created_at'),
                'charges' => $this->monthlySeries(Payment::query()->where('user_id', $user->id)->successful(), 'created_at', 'amount'),
            ],
            'breakdown' => $this->funnelPayload($statusCounts),
            'activity' => $this->seekerActivity($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function hr(User $user): array
    {
        $jobIds = Job::visibleTo($user)->pluck('id');
        $applications = JobApplication::whereIn('job_id', $jobIds);
        $interviews = Interview::whereIn('job_id', $jobIds);
        $subscription = $user->activeSubscription;
        $subscription?->load('paymentPlan');
        $payerIds = $this->companyUserIds($user);

        $funnel = JobApplication::query()
            ->whereIn('job_id', $jobIds)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $stats = [
            'active_jobs' => Job::visibleTo($user)->where('status', 'active')->count(),
            'total_jobs' => Job::visibleTo($user)->count(),
            'total_applicants' => (clone $applications)->count(),
            'under_review' => (clone $applications)->whereIn('status', ['pending', 'reviewing'])->count(),
            'interviews_pending_review' => (clone $interviews)
                ->where('status', 'completed')
                ->where('review_status', Interview::REVIEW_PENDING)
                ->count(),
            'interviews_completed' => (clone $interviews)->where('status', 'completed')->count(),
            'subscription_plan' => $subscription?->paymentPlan?->display_name
                ?? $subscription?->paymentPlan?->name
                ?? 'Free',
            'subscription_status' => $subscription?->status ?? 'none',
        ];

        return [
            'role' => User::ROLE_HR_PROFESSIONAL,
            'stats' => $stats,
            'funnel' => $this->funnelPayload($funnel),
            'charges' => $this->chargePayload(Payment::query()->whereIn('user_id', $payerIds)),
            'charts' => [
                'applications' => $this->monthlySeries((clone $applications), 'applied_at'),
                'interviews' => $this->monthlySeries((clone $interviews), 'created_at'),
                'charges' => $this->monthlySeries(Payment::query()->whereIn('user_id', $payerIds)->successful(), 'created_at', 'amount'),
            ],
            'activity' => $this->hrActivity($jobIds),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function admin(): array
    {
        $thisMonth = User::where('created_at', '>=', now()->startOfMonth())->count();
        $lastMonth = User::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth(),
        ])->count();

        $growth = $lastMonth === 0
            ? ($thisMonth > 0 ? 100 : 0)
            : (int) round((($thisMonth - $lastMonth) / $lastMonth) * 100);

        $stats = [
            'total_users' => User::count(),
            'job_seekers' => User::where('role', User::ROLE_JOB_SEEKER)->count(),
            'hr_professionals' => User::where('role', User::ROLE_HR_PROFESSIONAL)->count(),
            'companies' => Company::count(),
            'job_postings' => Job::count(),
            'applications' => JobApplication::count(),
            'revenue' => (float) Payment::successful()->sum('amount'),
            'growth' => $growth,
        ];

        return [
            'role' => User::ROLE_ADMIN,
            'stats' => $stats,
            'charges' => $this->chargePayload(Payment::query()),
            'charts' => [
                'users' => $this->monthlySeries(User::query(), 'created_at'),
                'applications' => $this->monthlySeries(JobApplication::query(), 'applied_at'),
                'charges' => $this->monthlySeries(Payment::successful(), 'created_at', 'amount'),
            ],
            'breakdown' => [
                ['status' => 'job_seeker', 'count' => $stats['job_seekers']],
                ['status' => 'hr_professional', 'count' => $stats['hr_professionals']],
                ['status' => 'admin', 'count' => User::where('role', User::ROLE_ADMIN)->count()],
            ],
            'activity' => $this->adminActivity(),
        ];
    }

    public function profileScore(User $user): ?int
    {
        $user->loadCount(['portfolioProjects', 'skillExpectations']);
        $cvScore = $user->latestProcessedCv?->review_score;
        $portfolioScore = $user->portfolio_projects_count > 0 ? min(100, $user->portfolio_projects_count * 20) : null;
        $skillsScore = $user->skill_expectations_count > 0 ? min(100, $user->skill_expectations_count * 15) : null;
        $parts = array_values(array_filter([$cvScore, $portfolioScore, $skillsScore], fn ($score) => $score !== null));

        return $parts === [] ? null : (int) round(array_sum($parts) / count($parts));
    }

    /**
     * @param  Collection<string, int|string>  $counts
     * @return array<int, array{status: string, count: int}>
     */
    public function funnelPayload(Collection $counts): array
    {
        $order = ['pending', 'reviewing', 'shortlisted', 'interviewed', 'accepted', 'rejected'];

        return array_map(fn (string $status) => [
            'status' => $status,
            'count' => (int) ($counts[$status] ?? 0),
        ], $order);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function seekerActivity(User $user): array
    {
        $applications = JobApplication::where('user_id', $user->id)
            ->with('job')
            ->latest('applied_at')
            ->take(5)
            ->get()
            ->map(fn (JobApplication $application) => [
                'type' => 'application',
                'title' => 'Applied to '.($application->job?->title ?? 'a job'),
                'detail' => str_replace('_', ' ', $application->status),
                'at' => $application->applied_at?->toIso8601String() ?? $application->created_at?->toIso8601String(),
                'href' => '/job-applications',
            ]);

        $interviews = Interview::where('candidate_id', $user->id)
            ->with('job')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Interview $interview) => [
                'type' => 'interview',
                'title' => ($interview->status === 'completed' ? 'Completed' : 'Assigned').' interview for '.($interview->job?->title ?? 'a job'),
                'detail' => str_replace('_', ' ', $interview->status),
                'at' => ($interview->completed_at ?? $interview->created_at)?->toIso8601String(),
                'href' => '/interviews/'.$interview->id,
            ]);

        return $this->sortActivity($applications->concat($interviews));
    }

    /**
     * @param  Collection<int, int>  $jobIds
     * @return array<int, array<string, mixed>>
     */
    protected function hrActivity(Collection $jobIds): array
    {
        $applications = JobApplication::whereIn('job_id', $jobIds)
            ->with(['user', 'job'])
            ->latest()
            ->take(6)
            ->get()
            ->map(fn (JobApplication $application) => [
                'type' => 'application',
                'title' => ($application->user?->name ?? 'Candidate').' applied to '.($application->job?->title ?? 'a job'),
                'detail' => str_replace('_', ' ', $application->status),
                'at' => $application->applied_at?->toIso8601String() ?? $application->created_at?->toIso8601String(),
                'href' => '/review-candidates',
            ]);

        $interviews = Interview::whereIn('job_id', $jobIds)
            ->with(['candidate', 'job'])
            ->where('status', 'completed')
            ->latest('completed_at')
            ->take(6)
            ->get()
            ->map(fn (Interview $interview) => [
                'type' => 'interview',
                'title' => ($interview->candidate?->name ?? 'Candidate').' completed '.($interview->job?->title ?? 'interview'),
                'detail' => $interview->review_status === Interview::REVIEW_PENDING ? 'Needs review' : str_replace('_', ' ', (string) $interview->review_status),
                'at' => $interview->completed_at?->toIso8601String(),
                'href' => '/interview-results/'.$interview->id,
            ]);

        return $this->sortActivity($applications->concat($interviews));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function adminActivity(): array
    {
        return User::latest()
            ->take(8)
            ->get()
            ->map(fn (User $user) => [
                'type' => 'user',
                'title' => $user->name.' joined',
                'detail' => str_replace('_', ' ', $user->role),
                'at' => $user->created_at?->toIso8601String(),
                'href' => '/user-management',
            ])
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function sortActivity(Collection $items): array
    {
        return $items
            ->filter(fn (array $item) => filled($item['at'] ?? null))
            ->sortByDesc('at')
            ->take(8)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function chargePayload(Builder $query): array
    {
        $successful = (clone $query)->successful();
        $thisMonthQuery = (clone $successful)->where('created_at', '>=', now()->startOfMonth());
        $lastMonthQuery = (clone $successful)->whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth(),
        ]);

        $thisMonth = (float) $thisMonthQuery->sum('amount');
        $lastMonth = (float) $lastMonthQuery->sum('amount');
        $total = (float) (clone $successful)->sum('amount');
        $count = (clone $successful)->count();

        $change = $lastMonth == 0.0
            ? ($thisMonth > 0 ? 100 : 0)
            : (int) round((($thisMonth - $lastMonth) / $lastMonth) * 100);

        return [
            'total' => $total,
            'count' => $count,
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'average' => $count > 0 ? round($total / $count, 2) : 0.0,
            'change' => $change,
            'recent' => $this->recentCharges($query),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function recentCharges(Builder $query, int $limit = 6): array
    {
        return (clone $query)
            ->successful()
            ->with(['user:id,name', 'paymentPlan:id,display_name,name'])
            ->latest('paid_at')
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn (Payment $payment) => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'currency' => strtoupper((string) $payment->currency),
                'status' => $payment->status,
                'type' => $payment->type,
                'description' => $payment->description
                    ?: ($payment->paymentPlan?->display_name ?? $payment->paymentPlan?->name ?? 'Charge'),
                'user' => $payment->user?->name,
                'at' => ($payment->paid_at ?? $payment->created_at)?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, key: string, value: float|int}>
     */
    protected function monthlySeries(Builder $query, string $column, ?string $sumColumn = null, int $months = 6): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $rows = (clone $query)
            ->where($column, '>=', $start)
            ->get([$column, ...($sumColumn ? [$sumColumn] : [])]);

        return collect(range($months - 1, 0))->map(function (int $offset) use ($rows, $column, $sumColumn): array {
            $month = now()->subMonths($offset);
            $inMonth = $rows->filter(function ($row) use ($column, $month) {
                $value = $row->{$column};

                if (! $value instanceof Carbon) {
                    $value = $value ? Carbon::parse($value) : null;
                }

                return $value?->isSameMonth($month) ?? false;
            });

            return [
                'label' => $month->format('M'),
                'key' => $month->format('Y-m'),
                'value' => $sumColumn
                    ? round((float) $inMonth->sum($sumColumn), 2)
                    : $inMonth->count(),
            ];
        })->all();
    }

    /**
     * @return Collection<int, int>
     */
    protected function companyUserIds(User $user): Collection
    {
        if ($user->company_id) {
            return User::query()->where('company_id', $user->company_id)->pluck('id');
        }

        return collect([$user->id]);
    }
}
