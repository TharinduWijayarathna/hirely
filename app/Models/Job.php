<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    protected $table = 'job_postings';

    protected $fillable = [
        'user_id',
        'company_id',
        'title',
        'description',
        'requirements',
        'location',
        'type',
        'remote',
        'salary_min',
        'salary_max',
        'salary_currency',
        'skills',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'skills' => 'array',
        'expires_at' => 'date',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function interviewTemplates(): HasMany
    {
        return $this->hasMany(InterviewTemplate::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->company_id) {
            return $query->where('company_id', $user->company_id);
        }

        return $query->where('user_id', $user->id);
    }
}
