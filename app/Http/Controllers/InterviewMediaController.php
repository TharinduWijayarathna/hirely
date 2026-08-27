<?php

namespace App\Http\Controllers;

use App\Models\Interview;
use App\Services\InterviewMediaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InterviewMediaController extends Controller
{
    public function recording(Interview $interview): StreamedResponse
    {
        $this->authorizeMedia($interview);

        abort_unless(filled($interview->recording_path) && Storage::disk('local')->exists($interview->recording_path), 404);

        $mime = str_ends_with($interview->recording_path, '.mp4') ? 'video/mp4' : 'video/webm';

        return Storage::disk('local')->response($interview->recording_path, 'interview-recording', [
            'Content-Type' => $mime,
        ]);
    }

    public function screenshot(Interview $interview, int $index, InterviewMediaService $media): StreamedResponse
    {
        $this->authorizeMedia($interview);

        $path = $media->screenshotPath($interview, $index);

        abort_unless(filled($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    protected function authorizeMedia(Interview $interview): void
    {
        $user = Auth::user();

        if ((int) $interview->candidate_id === (int) $user->id || $user->canAccessInterview($interview)) {
            return;
        }

        abort(403);
    }
}
