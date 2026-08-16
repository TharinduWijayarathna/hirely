<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_id',
        'cover_letter',
        'resume_path',
        'cv_document_id',
        'status',
        'notes',
        'applied_at',
        'ranking_score',
        'ranking_position',
        'ranking_breakdown',
        'ranked_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'ranked_at' => 'datetime',
        'ranking_score' => 'decimal:2',
        'ranking_position' => 'integer',
        'ranking_breakdown' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function cvDocument(): BelongsTo
    {
        return $this->belongsTo(CvDocument::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }
}
