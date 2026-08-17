<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GoogleTextToSpeechService
{
    public function configured(): bool
    {
        return filled(config('services.google.tts_api_key'));
    }

    public function synthesize(string $text): ?string
    {
        $text = trim($text);

        if ($text === '' || ! $this->configured()) {
            return null;
        }

        $text = mb_substr($text, 0, 4500);
        $disk = Storage::disk('local');
        $cachePath = 'tts/'.sha1(config('services.google.tts_voice').'|'.$text).'.mp3';

        if ($disk->exists($cachePath)) {
            return $disk->get($cachePath);
        }

        try {
            $response = Http::timeout(12)
                ->connectTimeout(5)
                ->acceptJson()
                ->withQueryParameters([
                    'key' => config('services.google.tts_api_key'),
                ])
                ->post('https://texttospeech.googleapis.com/v1/text:synthesize', [
                    'input' => ['text' => $text],
                    'voice' => [
                        'languageCode' => config('services.google.tts_language', 'en-US'),
                        'name' => config('services.google.tts_voice', 'en-US-Neural2-F'),
                        'ssmlGender' => 'FEMALE',
                    ],
                    'audioConfig' => [
                        'audioEncoding' => 'MP3',
                        'speakingRate' => 0.95,
                        'pitch' => 0,
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('Google TTS failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            $audio = base64_decode((string) $response->json('audioContent'), true);

            if ($audio === false || $audio === '') {
                return null;
            }

            $disk->put($cachePath, $audio);

            return $audio;
        } catch (Throwable $e) {
            Log::error('Google TTS exception: '.$e->getMessage());

            return null;
        }
    }
}
