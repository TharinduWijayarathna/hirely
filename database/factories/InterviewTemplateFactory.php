<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\InterviewTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InterviewTemplate>
 */
class InterviewTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->hrProfessional(),
            'company_id' => Company::factory(),
            'job_id' => null,
            'name' => 'Standard technical screen',
            'question_count' => 5,
            'duration_minutes' => 30,
            'difficulty' => 'intermediate',
            'mode' => 'voice',
            'technical_percentage' => 40,
            'behavioral_percentage' => 30,
            'scenario_percentage' => 20,
            'cv_percentage' => 10,
            'evaluation_criteria' => ['Technical depth', 'Communication', 'Problem solving'],
            'question_weights' => [
                'Technical depth' => 40,
                'Communication' => 30,
                'Problem solving' => 30,
            ],
            'is_active' => true,
        ];
    }
}
