<?php

namespace Database\Factories;

use App\Models\PaymentPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentPlan>
 */
class PaymentPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'professional',
            'display_name' => 'Professional Plan',
            'description' => 'For growing teams',
            'amount' => 49,
            'currency' => 'USD',
            'interval' => 'month',
            'is_active' => true,
            'target_role' => 'hr_professional',
            'sort_order' => 1,
            'limits' => [
                'jobs' => null,
                'reports' => true,
            ],
        ];
    }

    public function hrBasic(): static
    {
        return $this->state(fn () => [
            'name' => 'basic',
            'display_name' => 'Basic Plan',
            'amount' => 0,
            'target_role' => 'hr_professional',
            'limits' => [
                'jobs' => 5,
                'reports' => false,
            ],
        ]);
    }

    public function seekerFree(): static
    {
        return $this->state(fn () => [
            'name' => 'basic',
            'display_name' => 'Free Plan',
            'amount' => 0,
            'target_role' => 'job_seeker',
            'limits' => [
                'mock_interviews_per_month' => 3,
                'cv_documents' => 1,
                'ats' => false,
            ],
        ]);
    }

    public function seekerPremium(): static
    {
        return $this->state(fn () => [
            'name' => 'professional',
            'display_name' => 'Premium Plan',
            'amount' => 19.99,
            'target_role' => 'job_seeker',
            'limits' => [
                'mock_interviews_per_month' => null,
                'cv_documents' => null,
                'ats' => true,
            ],
        ]);
    }
}
