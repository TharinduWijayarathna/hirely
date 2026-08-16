<?php

namespace Database\Factories;

use App\Models\CvDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CvDocument>
 */
class CvDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->jobSeeker(),
            'original_name' => 'resume.pdf',
            'path' => 'cvs/test/resume.pdf',
            'disk' => 'local',
            'mime_type' => 'application/pdf',
            'size' => 12000,
            'parsed_text' => 'Jane Doe. Senior PHP Laravel developer with 7 years experience. Skills: PHP, Laravel, Vue, Docker.',
            'extraction' => [
                'full_name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'skills' => ['PHP', 'Laravel', 'Vue'],
                'technologies' => ['Docker'],
                'education' => [['institution' => 'State University', 'degree' => 'BSc', 'field' => 'CS']],
                'experience' => [['company' => 'Acme', 'title' => 'Engineer']],
                'qualifications' => ['BSc Computer Science'],
                'projects' => [['name' => 'Hirely', 'technologies' => ['Laravel']]],
                'certifications' => [['name' => 'AWS Cloud Practitioner']],
                'relevant_experience' => ['Built Laravel APIs'],
                'experience_years' => 7,
                'experience_level' => 'senior',
                'summary' => 'Senior PHP engineer',
            ],
            'review' => [
                'score' => 82,
                'summary' => 'Strong technical CV',
                'strengths' => ['Clear experience'],
                'improvements' => ['Add metrics'],
            ],
            'review_score' => 82,
            'status' => 'processed',
        ];
    }
}
