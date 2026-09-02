<?php

use App\Models\Company;
use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function voiceInterviewSetup(): array
{
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
    ]);
    $candidate = User::factory()->jobSeeker()->create();
    $application = JobApplication::factory()->create([
        'user_id' => $candidate->id,
        'job_id' => $job->id,
    ]);
    $interview = Interview::factory()->create([
        'interview_template_id' => InterviewTemplate::factory()->create([
            'user_id' => $hr->id,
            'company_id' => $company->id,
            'mode' => 'voice',
        ])->id,
        'job_application_id' => $application->id,
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'mode' => 'voice',
        'status' => 'in_progress',
    ]);

    return compact('company', 'hr', 'job', 'candidate', 'application', 'interview');
}

test('voice interviews return an error when google tts is not configured', function () {
    config(['services.google.tts_api_key' => '']);

    $setup = voiceInterviewSetup();

    $this->actingAs($setup['candidate'])
        ->postJson(route('interviews.speech', $setup['interview']), [
            'text' => 'Tell me about a recent Laravel project.',
        ])
        ->assertStatus(422)
        ->assertJson(['message' => 'Google Text-to-Speech is not configured.']);
});

test('voice interviews speak through the google tts api', function () {
    Storage::fake('local');
    config(['services.google.tts_api_key' => 'test-tts-key']);
    Http::fake([
        'texttospeech.googleapis.com/*' => Http::response([
            'audioContent' => base64_encode('fake-mp3-bytes'),
        ], 200),
    ]);

    $setup = voiceInterviewSetup();

    $this->actingAs($setup['candidate'])
        ->post(route('interviews.speech', $setup['interview']), [
            'text' => 'Walk me through a production incident you owned.',
        ])
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertSee('fake-mp3-bytes', false);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'texttospeech.googleapis.com'));
});

test('candidates can upload interview screenshots and a webrtc recording', function () {
    Storage::fake('local');
    $setup = voiceInterviewSetup();

    $this->actingAs($setup['candidate'])
        ->post(route('interviews.screenshots.store', $setup['interview']), [
            'screenshot' => UploadedFile::fake()->image('face.jpg', 640, 360),
            'label' => 'session_start',
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $this->actingAs($setup['candidate'])
        ->post(route('interviews.recording.store', $setup['interview']), [
            'recording' => UploadedFile::fake()->create('interview.webm', 120, 'video/webm'),
        ])
        ->assertOk()
        ->assertJson(['ok' => true]);

    $interview = $setup['interview']->fresh();

    expect($interview->screenshots)->toHaveCount(1)
        ->and($interview->recording_path)->not->toBeNull();

    Storage::disk('local')->assertExists($interview->recording_path);
    Storage::disk('local')->assertExists($interview->screenshots[0]['path']);
});

test('candidates can upload a random identity still during a voice interview', function () {
    Storage::fake('local');
    $setup = voiceInterviewSetup();

    $this->actingAs($setup['candidate'])
        ->post(route('interviews.screenshots.store', $setup['interview']), [
            'screenshot' => UploadedFile::fake()->image('random.jpg', 640, 360),
            'label' => 'random',
        ])
        ->assertOk();

    $shot = $setup['interview']->fresh()->screenshots[0];

    expect($shot['label'])->toBe('random')
        ->and($shot['captured_at'])->not->toBeEmpty();
});

test('hr can review the recording and outsiders cannot', function () {
    Storage::fake('local');
    $setup = voiceInterviewSetup();
    $path = 'interviews/'.$setup['interview']->id.'/recording.webm';
    Storage::disk('local')->put($path, 'webm-bytes');
    $setup['interview']->update(['recording_path' => $path]);

    $this->actingAs($setup['hr'])
        ->get(route('interview-media.recording', $setup['interview']))
        ->assertOk();

    $outsider = User::factory()->hrProfessional()->create();

    $this->actingAs($outsider)
        ->get(route('interview-media.recording', $setup['interview']))
        ->assertForbidden();
});
