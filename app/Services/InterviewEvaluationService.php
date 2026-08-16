<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\User;

class InterviewEvaluationService
{
    public function __construct(protected AIService $ai) {}

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public function complete(Interview $interview, array $answers): array
    {
        $interview->loadMissing(['job', 'template']);

        $criteria = $interview->evaluationCriteria();

        $evaluation = $this->ai->evaluateInterview(
            $interview->questions ?? [],
            $answers,
            $interview->difficulty,
            $criteria,
            $interview->job?->title,
        );

        $evaluation = $this->applyCriterionWeights($evaluation, $interview->criterionWeights());

        $score = $evaluation['overall_score'];

        $legacyFeedback = [];
        foreach ($evaluation['answers'] as $row) {
            $legacyFeedback[$row['question']] = $row['feedback'];
        }
        $legacyFeedback['overall'] = $evaluation['rationale'];

        return [
            'answers' => $answers,
            'evaluation' => $evaluation,
            'criteria' => $criteria,
            'feedback' => $legacyFeedback,
            'ai_score' => $score,
            'score' => $score,
            'review_status' => Interview::REVIEW_PENDING,
        ];
    }

    public function applyReview(
        Interview $interview,
        User $reviewer,
        string $action,
        string $notes,
        ?float $humanScore = null,
    ): Interview {
        $audit = $interview->review_audit ?? [];
        $audit[] = [
            'action' => $action,
            'user_id' => $reviewer->id,
            'user_name' => $reviewer->name,
            'at' => now()->toIso8601String(),
            'notes' => $notes,
            'human_score' => $action === Interview::REVIEW_EDITED ? $humanScore : null,
            'previous_score' => $interview->score,
            'ai_score' => $interview->ai_score,
        ];

        $score = $action === Interview::REVIEW_EDITED
            ? $humanScore
            : $interview->ai_score;

        $interview->update([
            'review_status' => $action,
            'human_score' => $action === Interview::REVIEW_EDITED ? $humanScore : $interview->human_score,
            'human_notes' => $notes,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_audit' => $audit,
            'score' => $score,
        ]);

        return $interview->fresh();
    }

    /**
     * @param  array<string, mixed>  $evaluation
     * @param  array<string, int>  $weights
     * @return array<string, mixed>
     */
    protected function applyCriterionWeights(array $evaluation, array $weights): array
    {
        $dimensions = $evaluation['dimensions'] ?? [];
        $totalWeight = 0;
        $weighted = 0;

        foreach ($dimensions as $index => $dimension) {
            $weight = max(1, (int) ($weights[$dimension['name'] ?? ''] ?? $dimension['weight'] ?? 1));
            $dimensions[$index]['weight'] = $weight;
            $totalWeight += $weight;
            $weighted += ((int) ($dimension['score'] ?? 0)) * $weight;
        }

        $evaluation['dimensions'] = $dimensions;

        if ($totalWeight > 0) {
            $evaluation['overall_score'] = (int) round($weighted / $totalWeight);
        }

        return $evaluation;
    }
}
