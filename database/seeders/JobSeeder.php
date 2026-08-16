<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class JobSeeder extends Seeder
{
    public function run(): void
    {
        $needed = 50 - Job::query()->count();

        if ($needed <= 0) {
            return;
        }

        $posters = $this->organizations();
        $titles = $this->titles();
        $types = ['full_time', 'full_time', 'full_time', 'part_time', 'contract', 'internship'];
        $remotes = ['on_site', 'remote', 'hybrid'];
        $skillSets = [
            ['PHP', 'Laravel', 'MySQL'],
            ['Vue', 'TypeScript', 'Tailwind'],
            ['Python', 'Django', 'PostgreSQL'],
            ['React', 'Node.js', 'AWS'],
            ['Java', 'Spring', 'Kafka'],
            ['Go', 'Kubernetes', 'gRPC'],
            ['Figma', 'Product design', 'Prototyping'],
            ['SQL', 'dbt', 'Looker'],
        ];

        for ($i = 0; $i < $needed; $i++) {
            $poster = $posters[$i % count($posters)];
            $title = $titles[$i % count($titles)];
            if ($i >= count($titles)) {
                $title .= ' '.$poster['company']->name;
            }

            Job::factory()->create([
                'user_id' => $poster['hr']->id,
                'company_id' => $poster['company']->id,
                'title' => $title,
                'description' => $this->description($title, $poster['company']->name),
                'requirements' => '3+ years in a related role, a portfolio or GitHub we can review, and written English.',
                'location' => fake()->randomElement([
                    $poster['company']->location ?: 'Colombo',
                    'Colombo',
                    'Kandy',
                    'Remote',
                    fake()->city(),
                ]),
                'type' => fake()->randomElement($types),
                'remote' => fake()->randomElement($remotes),
                'salary_min' => fake()->randomElement([800, 1200, 1500, 2000, 2500]),
                'salary_max' => fake()->randomElement([3000, 3500, 4000, 5000, 6500]),
                'salary_currency' => 'USD',
                'skills' => fake()->randomElement($skillSets),
                'status' => 'active',
                'expires_at' => now()->addDays(fake()->numberBetween(21, 90)),
            ]);
        }
    }

    /**
     * @return list<array{company: Company, hr: User}>
     */
    private function organizations(): array
    {
        $hirely = Company::query()->firstOrCreate(
            ['slug' => 'hirely-labs'],
            [
                'name' => 'Hirely Labs',
                'description' => 'Sample hiring organization for Hirely.',
                'industry' => 'Software',
                'size' => '11-50',
                'location' => 'Colombo',
                'is_verified' => true,
            ],
        );

        $hirelyHr = User::query()->firstOrCreate(
            ['email' => 'hr@hirely.test'],
            [
                'name' => 'HR Professional',
                'password' => Hash::make('password'),
                'role' => 'hr_professional',
                'company_id' => $hirely->id,
                'email_verified_at' => now(),
            ],
        );

        $catalog = [
            ['name' => 'Northwind Digital', 'slug' => 'northwind-digital', 'industry' => 'Software', 'location' => 'Colombo'],
            ['name' => 'Lanka Cloud', 'slug' => 'lanka-cloud', 'industry' => 'Cloud', 'location' => 'Colombo'],
            ['name' => 'Cinnamon Pay', 'slug' => 'cinnamon-pay', 'industry' => 'Fintech', 'location' => 'Colombo'],
            ['name' => 'Pearl Analytics', 'slug' => 'pearl-analytics', 'industry' => 'Data', 'location' => 'Kandy'],
            ['name' => 'Harbor Health', 'slug' => 'harbor-health', 'industry' => 'Healthcare', 'location' => 'Galle'],
            ['name' => 'Monsoon Retail', 'slug' => 'monsoon-retail', 'industry' => 'Retail', 'location' => 'Negombo'],
            ['name' => 'Atlas Logistics', 'slug' => 'atlas-logistics', 'industry' => 'Logistics', 'location' => 'Colombo'],
        ];

        $posters = [[
            'company' => $hirely,
            'hr' => $hirelyHr,
        ]];

        foreach ($catalog as $item) {
            $company = Company::query()->firstOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['name'].' hires through Hirely.',
                    'industry' => $item['industry'],
                    'size' => '11-50',
                    'location' => $item['location'],
                    'is_verified' => true,
                ],
            );

            $hr = User::query()->firstOrCreate(
                ['email' => 'hr@'.$item['slug'].'.test'],
                [
                    'name' => $item['name'].' HR',
                    'password' => Hash::make('password'),
                    'role' => 'hr_professional',
                    'company_id' => $company->id,
                    'email_verified_at' => now(),
                ],
            );

            $posters[] = ['company' => $company, 'hr' => $hr];
        }

        return $posters;
    }

    /**
     * @return list<string>
     */
    private function titles(): array
    {
        return [
            'Backend Engineer',
            'Frontend Engineer',
            'Full Stack Developer',
            'Laravel Developer',
            'Vue Engineer',
            'Platform Engineer',
            'DevOps Engineer',
            'Site Reliability Engineer',
            'QA Engineer',
            'Mobile Engineer',
            'iOS Developer',
            'Android Developer',
            'Data Engineer',
            'Data Analyst',
            'Machine Learning Engineer',
            'Product Designer',
            'Product Manager',
            'Engineering Manager',
            'Technical Writer',
            'Customer Success Manager',
            'Account Executive',
            'Recruiter',
            'Office Manager',
            'Finance Analyst',
            'Marketing Manager',
            'Content Designer',
            'Security Engineer',
            'Support Engineer',
            'Solutions Architect',
            'Cloud Engineer',
            'Intern Software Engineer',
            'Staff Engineer',
            'Principal Engineer',
            'UX Researcher',
            'Growth Marketer',
            'People Operations Lead',
            'Legal Counsel',
            'Sales Development Representative',
            'Business Analyst',
            'Database Administrator',
            'Network Engineer',
            'IT Support Specialist',
            'Graphic Designer',
            'Brand Designer',
            'Video Producer',
            'Community Manager',
            'Operations Coordinator',
            'Supply Chain Analyst',
            'Nurse Informaticist',
            'Payments Engineer',
            'Risk Analyst',
            'Warehouse Supervisor',
        ];
    }

    private function description(string $title, string $company): string
    {
        return "{$company} is hiring a {$title}. You will ship work with the team, talk to the people who use the product, and leave a trail others can review.\n\nThis posting is live on Hirely. Apply from the public job page; if we have an interview template on the role, you will be assigned that interview after you apply.";
    }
}
