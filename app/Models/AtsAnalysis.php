<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtsAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cv_document_id',
        'job_id',
        'job_description',
        'score',
        'analysis',
    ];

    protected $casts = [
        'analysis' => 'array',
        'score' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cvDocument(): BelongsTo
    {
        return $this->belongsTo(CvDocument::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
