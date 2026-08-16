<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interview extends Model
{
    use HasFactory;

    public const REVIEW_PENDING = 'pending_review';

    public const REVIEW_ACCEPTED = 'accepted';

    public const REVIEW_EDITED = 'edited';

    public const REVIEW_REJECTED = 'rejected';

    public const DEFAULT_CRITERIA = [
        'Technical depth',
        'Communication',
        'Problem solving',
        'Role fit',
    ];

    protected $fillable = [
        'interview_template_id',
        'job_application_id',
        'job_id',
        'candidate_id',
        'assigned_by',
        'difficulty',
        'mode',
        'status',
        'questions',
        'answers',
        'conversation_history',
        'feedback',
        'evaluation',
        'criteria',
        'question_weights',
        'score',
        'ai_score',
        'human_score',
        'human_notes',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'review_audit',
        'started_at',
        'completed_at',
        'duration_minutes',
        'recording_path',
        'screenshots',
    ];

    protected $casts = [
        'questions' => 'array',
        'answers' => 'array',
        'conversation_history' => 'array',
        'feedback' => 'array',
        'evaluation' => 'array',
        'criteria' => 'array',
        'question_weights' => 'array',
        'review_audit' => 'array',
        'score' => 'decimal:2',
        'ai_score' => 'decimal:2',
        'human_score' => 'decimal:2',
        'started_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'screenshots' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(InterviewTemplate::class, 'interview_template_id');
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return array<int, string>
     */
    public function evaluationCriteria(): array
    {
        $criteria = $this->criteria
            ?? $this->template?->evaluation_criteria
            ?? [];

        $criteria = array_values(array_filter(
            is_array($criteria) ? $criteria : [],
            fn ($item) => is_string($item) && trim($item) !== ''
        ));

        return $criteria === [] ? self::DEFAULT_CRITERIA : $criteria;
    }

    /**
     * @return array<string, int>
     */
    public function criterionWeights(): array
    {
        $weights = $this->question_weights ?? $this->template?->question_weights ?? [];
        $criteria = $this->evaluationCriteria();
        $equal = $criteria === [] ? 1 : (int) max(1, intdiv(100, count($criteria)));
        $normalized = [];

        foreach ($criteria as $name) {
            $normalized[$name] = max(1, (int) ($weights[$name] ?? $equal));
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function toResultPayload(bool $forHr = false): array
    {
        $payload = [
            'id' => $this->id,
            'status' => $this->status,
            'difficulty' => $this->difficulty,
            'mode' => $this->mode,
            'score' => $this->score !== null ? (float) $this->score : null,
            'ai_score' => $this->ai_score !== null ? (float) $this->ai_score : null,
            'human_score' => $this->human_score !== null ? (float) $this->human_score : null,
            'review_status' => $this->review_status,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'evaluation' => $this->evaluation,
            'criteria' => $this->evaluationCriteria(),
            'questions' => $this->questions,
            'answers' => $this->answers,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'duration_minutes' => $this->duration_minutes,
            'recording_url' => $this->recording_path ? '/interview-media/'.$this->id.'/recording' : null,
            'screenshots' => collect($this->screenshots ?? [])
                ->values()
                ->map(fn (array $shot, int $index) => [
                    'url' => '/interview-media/'.$this->id.'/screenshots/'.$index,
                    'label' => $shot['label'] ?? 'capture',
                    'captured_at' => $shot['captured_at'] ?? null,
                ])
                ->all(),
            'created_at' => $this->created_at?->toIso8601String(),
            'job' => $this->job ? [
                'id' => $this->job->id,
                'title' => $this->job->title,
            ] : null,
            'candidate' => $this->candidate ? [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'email' => $this->candidate->email,
            ] : null,
            'template' => $this->template ? [
                'id' => $this->template->id,
                'name' => $this->template->name,
            ] : null,
        ];

        if ($forHr) {
            $payload['human_notes'] = $this->human_notes;
            $payload['review_audit'] = $this->review_audit ?? [];
            $payload['reviewed_by'] = $this->reviewer ? [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ] : null;
        }

        return $payload;
    }

    public function isUsableForRanking(): bool
    {
        return $this->status === 'completed'
            && $this->review_status !== self::REVIEW_REJECTED
            && $this->score !== null;
    }

    public function questionTexts(): array
    {
        return collect($this->questions ?? [])
            ->map(fn ($question) => is_array($question) ? ($question['text'] ?? '') : $question)
            ->filter()
            ->values()
            ->all();
    }
}
