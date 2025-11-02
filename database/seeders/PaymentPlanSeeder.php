<?php

namespace Database\Seeders;

use App\Models\PaymentPlan;
use Illuminate\Database\Seeder;

class PaymentPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            // HR Professional Plans
            [
                'name' => 'basic',
                'display_name' => 'Basic Plan',
                'description' => 'Free tier for HR professionals',
                'amount' => 0.00,
                'currency' => 'USD',
                'interval' => 'month',
                'stripe_price_id' => null, // Set this after creating in Stripe
                'stripe_product_id' => null,
                'features' => [
                    'Post up to 5 job listings',
                    'View candidate applications',
                    'Basic candidate filtering',
                ],
                'is_active' => true,
                'target_role' => 'hr_professional',
                'sort_order' => 1,
            ],
            [
                'name' => 'professional',
                'display_name' => 'Professional Plan',
                'description' => 'For growing HR teams',
                'amount' => 49.00,
                'currency' => 'USD',
                'interval' => 'month',
                'stripe_price_id' => null, // Set this after creating in Stripe
                'stripe_product_id' => null,
                'features' => [
                    'Unlimited job postings',
                    'Advanced candidate filtering',
                    'Priority support',
                    'Analytics dashboard',
                    'Bulk candidate management',
                ],
                'is_active' => true,
                'target_role' => 'hr_professional',
                'sort_order' => 2,
            ],
            [
                'name' => 'enterprise',
                'display_name' => 'Enterprise Plan',
                'description' => 'For large organizations',
                'amount' => 99.00,
                'currency' => 'USD',
                'interval' => 'month',
                'stripe_price_id' => null, // Set this after creating in Stripe
                'stripe_product_id' => null,
                'features' => [
                    'Everything in Professional',
                    'Dedicated account manager',
                    'Custom integrations',
                    'SLA guarantee',
                    'Onboarding support',
                ],
                'is_active' => true,
                'target_role' => 'hr_professional',
                'sort_order' => 3,
            ],
            // Job Seeker Plans
            [
                'name' => 'basic',
                'display_name' => 'Free Plan',
                'description' => 'Basic features for job seekers',
                'amount' => 0.00,
                'currency' => 'USD',
                'interval' => 'month',
                'stripe_price_id' => null,
                'stripe_product_id' => null,
                'features' => [
                    'Apply to jobs',
                    'View job listings',
                    'Basic profile features',
                ],
                'is_active' => true,
                'target_role' => 'job_seeker',
                'sort_order' => 1,
            ],
            [
                'name' => 'professional',
                'display_name' => 'Premium Plan',
                'description' => 'Unlock all premium features',
                'amount' => 19.99,
                'currency' => 'USD',
                'interval' => 'month',
                'stripe_price_id' => null, // Set this after creating in Stripe
                'stripe_product_id' => null,
                'features' => [
                    'Unlimited mock interviews',
                    'Advanced CV review',
                    'Priority job recommendations',
                    'ATS score optimization',
                    'Career coaching tips',
                ],
                'is_active' => true,
                'target_role' => 'job_seeker',
                'sort_order' => 2,
            ],
        ];

        foreach ($plans as $plan) {
            PaymentPlan::updateOrCreate(
                ['name' => $plan['name'], 'target_role' => $plan['target_role']],
                $plan
            );
        }
    }
}
