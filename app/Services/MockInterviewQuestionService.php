<?php

namespace App\Services;

use App\Models\User;

class MockInterviewQuestionService
{
    public const QUESTION_COUNT = 10;

    public function __construct(protected AIService $ai) {}

    /**
     * @return array<int, array{category: string, text: string}>
     */
    public function generate(User $user, string $type, string $difficulty): array
    {
        $user->loadMissing('latestProcessedCv');
        $extraction = $user->latestProcessedCv?->extraction;
        $role = $this->practiceRole($extraction);

        $questions = $this->ai->generateConfiguredQuestions(
            $difficulty,
            $this->categoryCounts($type),
            $role['title'],
            $role['description'],
            $user->candidateContext(),
            ['Technical depth', 'Communication', 'Problem solving', 'CV relevance'],
        );

        if ($questions === []) {
            $questions = $this->ai->fallbackConfiguredQuestions($difficulty, $this->categoryCounts($type));
        }

        return $questions;
    }

    /**
     * @return array<string, int>
     */
    public function categoryCounts(string $type): array
    {
        return match ($type) {
            'technical' => [
                'technical' => 6,
                'scenario' => 1,
                'cv' => 3,
            ],
            'behavioral' => [
                'behavioral' => 5,
                'scenario' => 2,
                'cv' => 3,
            ],
            default => [
                'technical' => 4,
                'behavioral' => 2,
                'scenario' => 2,
                'cv' => 2,
            ],
        };
    }

    /**
     * @param  array<string, mixed>|null  $extraction
     * @return array{title: string, description: string}
     */
    protected function practiceRole(?array $extraction): array
    {
        $experience = is_array($extraction['experience'][0] ?? null) ? $extraction['experience'][0] : [];
        $title = trim((string) ($experience['title'] ?? ''));

        if ($title === '') {
            $title = 'Software Engineer';
        }

        $lines = array_filter([
            $extraction['summary'] ?? null,
            ! empty($extraction['skills']) ? 'Skills from the candidate CV: '.implode(', ', $extraction['skills']) : null,
            ! empty($extraction['technologies']) ? 'Technologies: '.implode(', ', $extraction['technologies']) : null,
            ! empty($experience['company']) ? 'Most recent company: '.$experience['company'] : null,
        ]);

        return [
            'title' => $title,
            'description' => implode("\n", $lines),
        ];
    }
}
