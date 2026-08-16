<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->jobSeeker(),
            'job_id' => Job::factory(),
            'cover_letter' => fake()->paragraph(),
            'status' => 'pending',
            'applied_at' => now(),
        ];
    }
}
