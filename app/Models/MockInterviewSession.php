<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockInterviewSession extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'difficulty',
        'mode',
        'status',
        'questions',
        'answers',
        'conversation_history',
        'feedback',
        'evaluation',
        'score',
        'started_at',
        'completed_at',
        'duration_minutes',
    ];

    protected $casts = [
        'questions' => 'array',
        'answers' => 'array',
        'conversation_history' => 'array',
        'feedback' => 'array',
        'evaluation' => 'array',
        'score' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toResultPayload(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'mode' => $this->mode,
            'status' => $this->status,
            'score' => $this->score !== null ? (float) $this->score : null,
            'evaluation' => $this->resolvedEvaluation(),
            'answers' => $this->answers,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function resolvedEvaluation(): ?array
    {
        if (is_array($this->evaluation) && $this->evaluation !== []) {
            return $this->evaluation;
        }

        $feedback = $this->feedback ?? [];
        $answers = $this->answers ?? [];

        if ($feedback === []) {
            return null;
        }

        $rows = [];
        foreach ($feedback as $question => $value) {
            if ($question === 'overall' || ! is_string($question)) {
                continue;
            }

            $rows[] = [
                'question' => $question,
                'category' => 'question',
                'score' => 0,
                'feedback' => is_string($value) ? $value : '',
                'answer' => $answers[$question] ?? '',
            ];
        }

        if ($rows === []) {
            return null;
        }

        return [
            'overall_score' => $this->score !== null ? (int) round((float) $this->score) : null,
            'rationale' => isset($feedback['overall']) ? (string) $feedback['overall'] : null,
            'answers' => $rows,
        ];
    }
}
