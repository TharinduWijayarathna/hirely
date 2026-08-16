<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Job extends Model
{
    use HasFactory;

    protected $table = 'job_postings';

    protected $fillable = [
        'user_id',
        'company_id',
        'title',
        'slug',
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

    protected $appends = [
        'public_url',
    ];

    protected static function booted(): void
    {
        static::creating(function (Job $job): void {
            if (empty($job->slug)) {
                $job->slug = static::uniqueSlug($job->title);
            }
        });
    }

    public function isPubliclyListed(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->endOfDay()->isFuture();
    }

    public function scopePubliclyListed($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now()->toDateString());
            });
    }

    public function publicUrl(): string
    {
        if (! $this->slug) {
            return url('/jobs');
        }

        return route('jobs.show', $this);
    }

    public function getPublicUrlAttribute(): string
    {
        return $this->publicUrl();
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'role';
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
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
