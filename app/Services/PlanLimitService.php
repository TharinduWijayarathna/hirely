<?php

namespace App\Services;

use App\Models\AtsAnalysis;
use App\Models\CvDocument;
use App\Models\Job;
use App\Models\MockInterviewSession;
use App\Models\PaymentPlan;
use App\Models\User;

class PlanLimitService
{
    /**
     * @return array<string, mixed>
     */
    public function limitsFor(User $user): array
    {
        if ($user->isAdmin() || ! $this->paymentsRequired()) {
            return [
                'jobs' => null,
                'reports' => true,
                'mock_interviews_per_month' => null,
                'cv_documents' => null,
                'ats' => true,
            ];
        }

        $defaults = $user->isHrProfessional()
            ? ['jobs' => 5, 'reports' => false]
            : ['mock_interviews_per_month' => 3, 'cv_documents' => 1, 'ats' => false];

        $plan = $this->currentPlan($user);

        return array_merge($defaults, $plan?->limits ?? []);
    }

    public function paymentsRequired(): bool
    {
        return (bool) config('payments.required');
    }

    public function currentPlan(User $user): ?PaymentPlan
    {
        $subscription = $user->activeSubscription?->loadMissing('paymentPlan');

        if ($subscription?->paymentPlan) {
            return $subscription->paymentPlan;
        }

        return PaymentPlan::query()
            ->where('target_role', $user->role)
            ->where('amount', 0)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function quota(User $user, string $feature): array
    {
        $limits = $this->limitsFor($user);
        $plan = $this->currentPlan($user);
        $planName = $plan?->display_name ?? ($user->isHrProfessional() ? 'Basic Plan' : 'Free Plan');
        $billing = route($user->billingRouteName());

        return match ($feature) {
            'jobs' => $this->numericQuota(
                $planName,
                $billing,
                Job::visibleTo($user)->count(),
                $this->numericLimit($limits, 'jobs', 5),
                "Your {$planName} allows {$this->limitLabel($this->numericLimit($limits, 'jobs', 5))} job listings. Upgrade to post more.",
            ),
            'mock_interviews' => $this->numericQuota(
                $planName,
                $billing,
                MockInterviewSession::where('user_id', $user->id)->where('created_at', '>=', now()->startOfMonth())->count(),
                $this->numericLimit($limits, 'mock_interviews_per_month', 3),
                "Your {$planName} includes {$this->limitLabel($this->numericLimit($limits, 'mock_interviews_per_month', 3))} mock interviews this month. Upgrade for unlimited practice.",
            ),
            'cv_documents' => $this->numericQuota(
                $planName,
                $billing,
                CvDocument::where('user_id', $user->id)->count(),
                $this->numericLimit($limits, 'cv_documents', 1),
                "Your {$planName} allows {$this->limitLabel($this->numericLimit($limits, 'cv_documents', 1))} stored CV. Upgrade for advanced CV review.",
            ),
            'ats' => $this->booleanQuota(
                $planName,
                $billing,
                (bool) ($limits['ats'] ?? false),
                'ATS scoring is included on Premium. Upgrade to compare your CV to job descriptions.',
                AtsAnalysis::where('user_id', $user->id)->count(),
            ),
            'reports' => $this->booleanQuota(
                $planName,
                $billing,
                (bool) ($limits['reports'] ?? false),
                'Recruitment reports are included on Professional and Enterprise plans.',
            ),
            default => ['allowed' => true, 'plan_name' => $planName, 'billing_url' => $billing],
        };
    }

    public function denyMessage(User $user, string $feature): ?string
    {
        $quota = $this->quota($user, $feature);

        return $quota['allowed'] ? null : ($quota['message'] ?? 'Upgrade your plan to continue.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function numericQuota(string $planName, string $billing, int $used, mixed $limit, string $message): array
    {
        $allowed = $limit === null || $used < (int) $limit;

        return [
            'allowed' => $allowed,
            'used' => $used,
            'limit' => $limit,
            'plan_name' => $planName,
            'billing_url' => $billing,
            'message' => $allowed ? null : $message,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function booleanQuota(string $planName, string $billing, bool $allowed, string $message, ?int $used = null): array
    {
        return [
            'allowed' => $allowed,
            'used' => $used,
            'limit' => $allowed ? null : 0,
            'plan_name' => $planName,
            'billing_url' => $billing,
            'message' => $allowed ? null : $message,
        ];
    }

    protected function numericLimit(array $limits, string $key, mixed $default): mixed
    {
        return array_key_exists($key, $limits) ? $limits[$key] : $default;
    }

    protected function limitLabel(mixed $limit): string
    {
        return $limit === null ? 'unlimited' : (string) $limit;
    }
}
