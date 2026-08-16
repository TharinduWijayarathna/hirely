<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CvDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'parsed_text',
        'extraction',
        'review',
        'review_score',
        'status',
        'error_message',
    ];

    protected $casts = [
        'extraction' => 'array',
        'review' => 'array',
        'review_score' => 'integer',
        'size' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atsAnalyses(): HasMany
    {
        return $this->hasMany(AtsAnalysis::class);
    }

    public function skills(): array
    {
        $extraction = $this->extraction ?? [];
        $skills = $extraction['skills'] ?? [];
        $technologies = $extraction['technologies'] ?? [];

        return array_values(array_unique(array_filter([...$skills, ...$technologies])));
    }

    public function experienceLevel(): ?string
    {
        return $this->extraction['experience_level'] ?? null;
    }

    public function absolutePath(): string
    {
        $disk = Storage::disk($this->disk);

        if (config("filesystems.disks.{$this->disk}.driver") === 'local') {
            return $disk->path($this->path);
        }

        $temporary = tempnam(sys_get_temp_dir(), 'hirely-cv-');
        file_put_contents($temporary, $disk->get($this->path));

        return $temporary;
    }
}
