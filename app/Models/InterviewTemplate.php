<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'job_id',
        'name',
        'question_count',
        'duration_minutes',
        'difficulty',
        'mode',
        'technical_percentage',
        'behavioral_percentage',
        'scenario_percentage',
        'cv_percentage',
        'evaluation_criteria',
        'question_weights',
        'is_active',
    ];

    protected $casts = [
        'evaluation_criteria' => 'array',
        'question_weights' => 'array',
        'is_active' => 'boolean',
        'question_count' => 'integer',
        'duration_minutes' => 'integer',
        'technical_percentage' => 'integer',
        'behavioral_percentage' => 'integer',
        'scenario_percentage' => 'integer',
        'cv_percentage' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function mixTotal(): int
    {
        return $this->technical_percentage
            + $this->behavioral_percentage
            + $this->scenario_percentage
            + $this->cv_percentage;
    }

    public function categoryCounts(): array
    {
        $total = max(1, $this->question_count);
        $categories = [
            'technical' => $this->technical_percentage,
            'behavioral' => $this->behavioral_percentage,
            'scenario' => $this->scenario_percentage,
            'cv' => $this->cv_percentage,
        ];

        $counts = [];
        $assigned = 0;
        $remaining = $categories;

        arsort($remaining);

        foreach ($remaining as $category => $percentage) {
            $counts[$category] = (int) floor($total * ($percentage / 100));
            $assigned += $counts[$category];
        }

        $leftover = $total - $assigned;
        foreach (array_keys($remaining) as $category) {
            if ($leftover <= 0) {
                break;
            }
            $counts[$category]++;
            $leftover--;
        }

        return $counts;
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->company_id) {
            return $query->where('company_id', $user->company_id);
        }

        return $query->where('user_id', $user->id);
    }
}
