<?php

namespace Database\Factories;

use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'interview_template_id' => InterviewTemplate::factory(),
            'job_application_id' => JobApplication::factory(),
            'job_id' => fn (array $attributes) => JobApplication::find($attributes['job_application_id'])->job_id,
            'candidate_id' => fn (array $attributes) => JobApplication::find($attributes['job_application_id'])->user_id,
            'difficulty' => 'intermediate',
            'mode' => 'text',
            'status' => 'pending',
            'questions' => [
                ['category' => 'technical', 'text' => 'Explain REST vs GraphQL.'],
                ['category' => 'behavioral', 'text' => 'Tell me about a challenge you faced.'],
            ],
        ];
    }
}
