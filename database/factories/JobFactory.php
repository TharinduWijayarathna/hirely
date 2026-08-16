<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->hrProfessional(),
            'company_id' => Company::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraphs(2, true),
            'requirements' => fake()->sentence(),
            'location' => fake()->city(),
            'type' => 'full_time',
            'remote' => 'hybrid',
            'status' => 'active',
            'skills' => ['PHP', 'Laravel', 'Vue'],
        ];
    }
}
