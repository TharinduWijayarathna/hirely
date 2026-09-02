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
}
