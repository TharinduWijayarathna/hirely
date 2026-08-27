<?php

namespace App\Models;

use App\Support\PostAuthRedirect;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    public const ROLE_JOB_SEEKER = 'job_seeker';

    public const ROLE_HR_PROFESSIONAL = 'hr_professional';

    public const ROLE_ADMIN = 'admin';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_id',
        'stripe_customer_id',
        'pm_type',
        'pm_last_four',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasVerifiedEmail(): bool
    {
        if (! PostAuthRedirect::emailVerificationEnabled()) {
            return true;
        }

        return ! is_null($this->email_verified_at);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription !== null && $this->activeSubscription->isActive();
    }

    public function getTierAttribute(): string
    {
        $activeSubscription = $this->activeSubscription()->with('paymentPlan')->first();
        return $activeSubscription ? $activeSubscription->paymentPlan->name : 'basic';
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isJobSeeker(): bool
    {
        return $this->hasRole(self::ROLE_JOB_SEEKER);
    }

    public function isHrProfessional(): bool
    {
        return $this->hasRole(self::ROLE_HR_PROFESSIONAL);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function billingRouteName(): string
    {
        return $this->isHrProfessional() ? 'subscriptions' : 'payments';
    }

    public function canAccessJob(Job $job): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isHrProfessional()) {
            return false;
        }

        if ($this->company_id && $job->company_id) {
            return (int) $this->company_id === (int) $job->company_id;
        }

        return (int) $job->user_id === (int) $this->id;
    }

    public function canAccessInterview(Interview $interview): bool
    {
        $interview->loadMissing('job');

        return $interview->job !== null && $this->canAccessJob($interview->job);
    }

    public function portfolioProjects(): HasMany
    {
        return $this->hasMany(PortfolioProject::class);
    }

    public function skillExpectations(): HasMany
    {
        return $this->hasMany(SkillExpectation::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class, 'candidate_id');
    }

    public function cvDocuments(): HasMany
    {
        return $this->hasMany(CvDocument::class);
    }

    public function latestProcessedCv(): HasOne
    {
        return $this->hasOne(CvDocument::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('status', 'processed')
        );
    }

    public function atsAnalyses(): HasMany
    {
        return $this->hasMany(AtsAnalysis::class);
    }

    public function candidateContext(): string
    {
        $parts = [];

        $cv = $this->latestProcessedCv;
        if ($cv?->extraction) {
            $extraction = $cv->extraction;
            if (! empty($extraction['full_name'])) {
                $parts[] = 'Name: '.$extraction['full_name'];
            }
            if (! empty($extraction['summary'])) {
                $parts[] = 'CV summary: '.$extraction['summary'];
            }
            $cvSkills = array_filter($extraction['skills'] ?? []);
            if ($cvSkills !== []) {
                $parts[] = 'CV skills: '.implode(', ', $cvSkills);
            }
            $technologies = array_filter($extraction['technologies'] ?? []);
            if ($technologies !== []) {
                $parts[] = 'Technologies: '.implode(', ', $technologies);
            }
            $experience = collect($extraction['experience'] ?? [])
                ->map(function ($row): string {
                    if (! is_array($row)) {
                        return trim((string) $row);
                    }

                    $role = trim(($row['title'] ?? '').' at '.($row['company'] ?? ''));
                    $description = trim((string) ($row['description'] ?? ''));

                    return trim($role.($description !== '' ? ': '.$description : ''), ' at:');
                })
                ->filter()
                ->take(5)
                ->implode('; ');
            if ($experience !== '') {
                $parts[] = 'Work history: '.$experience;
            }
            $cvProjects = collect($extraction['projects'] ?? [])
                ->map(function ($project): string {
                    if (is_string($project)) {
                        return $project;
                    }
                    if (! is_array($project)) {
                        return '';
                    }

                    $tech = is_array($project['technologies'] ?? null) ? implode(', ', $project['technologies']) : '';

                    return trim(($project['name'] ?? '').($tech !== '' ? " ({$tech})" : '').(! empty($project['description']) ? ': '.$project['description'] : ''));
                })
                ->filter()
                ->take(5)
                ->implode('; ');
            if ($cvProjects !== '') {
                $parts[] = 'CV projects: '.$cvProjects;
            }
            $education = collect($extraction['education'] ?? [])
                ->map(function ($row): string {
                    if (! is_array($row)) {
                        return trim((string) $row);
                    }

                    return trim(($row['degree'] ?? '').' '.($row['field'] ?? '').' at '.($row['institution'] ?? ''));
                })
                ->filter()
                ->take(3)
                ->implode('; ');
            if ($education !== '') {
                $parts[] = 'Education: '.$education;
            }
            if (! empty($extraction['relevant_experience'])) {
                $parts[] = 'Relevant experience: '.implode('; ', $extraction['relevant_experience']);
            }
        }

        $skills = $this->skillExpectations()->pluck('skill_name')->filter()->all();
        if ($skills !== []) {
            $parts[] = 'Skills: '.implode(', ', $skills);
        }

        $projects = $this->portfolioProjects()
            ->get(['title', 'description', 'technologies'])
            ->map(function (PortfolioProject $project): string {
                $tech = is_array($project->technologies) ? implode(', ', $project->technologies) : '';

                return trim($project->title.($tech !== '' ? " ({$tech})" : '').($project->description ? ": {$project->description}" : ''));
            })
            ->filter()
            ->all();

        if ($projects !== []) {
            $parts[] = 'Projects: '.implode('; ', $projects);
        }

        return implode("\n", $parts);
    }
}
