<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'website',
        'logo',
        'industry',
        'size',
        'location',
        'address',
        'phone',
        'email',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    public function hrProfessionals(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'hr_professional');
    }
}
