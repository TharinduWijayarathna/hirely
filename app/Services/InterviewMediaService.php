<?php

namespace App\Services;

use App\Models\Interview;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InterviewMediaService
{
    public const MAX_SCREENSHOTS = 40;

    public function storeScreenshot(Interview $interview, UploadedFile $file, ?string $label = null): array
    {
        return DB::transaction(function () use ($interview, $file, $label) {
            $locked = Interview::query()->lockForUpdate()->findOrFail($interview->id);
            $shots = $locked->screenshots ?? [];

            if (count($shots) >= self::MAX_SCREENSHOTS) {
                return $shots;
            }

            $path = $file->store("interviews/{$interview->id}/screenshots", 'local');

            $shots[] = [
                'path' => $path,
                'label' => $label ?: 'capture',
                'captured_at' => now()->toIso8601String(),
            ];

            $locked->update(['screenshots' => $shots]);

            return $shots;
        });
    }

    public function storeRecording(Interview $interview, UploadedFile $file): string
    {
        if ($interview->recording_path) {
            Storage::disk('local')->delete($interview->recording_path);
        }

        $extension = str_contains((string) $file->getMimeType(), 'mp4') ? 'mp4' : 'webm';
        $path = $file->storeAs("interviews/{$interview->id}", 'recording.'.$extension, 'local');

        $interview->update(['recording_path' => $path]);

        return $path;
    }

    public function screenshotPath(Interview $interview, int $index): ?string
    {
        $shot = ($interview->screenshots ?? [])[$index] ?? null;

        return is_array($shot) ? ($shot['path'] ?? null) : null;
    }
}
