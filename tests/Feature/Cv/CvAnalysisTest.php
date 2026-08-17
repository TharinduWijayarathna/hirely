<?php

use App\Models\Company;
use App\Models\CvDocument;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config(['services.gemini.api_key' => '']);
});

test('job seekers can upload a cv and see extracted skills', function () {
    Storage::fake('local');
    Storage::fake((string) config('filesystems.cv', 'local'));
    config(['services.gemini.api_key' => 'gemini-test']);
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'extraction' => [
                                'full_name' => 'Alex Rivera',
                                'email' => 'alex@example.com',
                                'skills' => ['php', 'laravel', 'vue'],
                                'experience_years' => 4,
                                'experience_level' => 'mid',
                            ],
                            'review' => [
                                'score' => 78,
                                'summary' => 'Strong PHP Laravel profile.',
                                'strengths' => ['Relevant stack'],
                                'improvements' => ['Add metrics'],
                            ],
                        ]),
                    ]],
                ],
            ]],
        ]),
    ]);
    $user = User::factory()->jobSeeker()->create();

    $this->actingAs($user)
        ->post(route('cv-review.store'), [
            'cv' => UploadedFile::fake()->createWithContent('resume.pdf', "%PDF-1.4\nHirely test resume"),
        ])
        ->assertRedirect(route('cv-review'));

    $document = CvDocument::where('user_id', $user->id)->first();

    expect($document)->not->toBeNull()
        ->and($document->status)->toBe('processed')
        ->and($document->extraction['skills'] ?? [])->toContain('php')
        ->and($document->review['summary'] ?? '')->toContain('PHP Laravel');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'generateContent')
        && str_contains($request->body(), 'inlineData'));
});

test('hr can filter candidates by extracted skills', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $match = User::factory()->jobSeeker()->create(['name' => 'Skill Match']);
    $other = User::factory()->jobSeeker()->create(['name' => 'Other Person']);
    CvDocument::factory()->create(['user_id' => $match->id]);
    CvDocument::factory()->create([
        'user_id' => $other->id,
        'extraction' => [
            'skills' => ['Python'],
            'experience_level' => 'entry',
        ],
    ]);

    $this->actingAs($hr)
        ->get(route('filter-candidates', ['skills' => 'Laravel']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hr/FilterCandidates')
            ->has('candidates.data', 1)
            ->where('candidates.data.0.name', 'Skill Match')
        );
});

test('applications attach the latest processed cv', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create(['user_id' => $hr->id, 'company_id' => $company->id, 'status' => 'active']);
    $seeker = User::factory()->jobSeeker()->create();
    $cv = CvDocument::factory()->create(['user_id' => $seeker->id]);

    $this->actingAs($seeker)
        ->post(route('job-applications.store'), [
            'job_id' => $job->id,
            'cover_letter' => 'I would like this role.',
        ])
        ->assertRedirect(route('browse-jobs'));

    expect(JobApplication::first()->cv_document_id)->toBe($cv->id);
});

test('ats scoring requires a processed cv', function () {
    $user = User::factory()->jobSeeker()->create();
    subscribeToPlan($user, [
        'mock_interviews_per_month' => null,
        'cv_documents' => null,
        'ats' => true,
    ], [
        'name' => 'professional',
        'display_name' => 'Premium Plan',
        'amount' => 19.99,
        'target_role' => 'job_seeker',
    ]);

    $this->actingAs($user)
        ->post(route('ats-scoring.store'), [
            'job_description' => str_repeat('Looking for a Laravel engineer. ', 5),
        ])
        ->assertRedirect(route('ats-scoring'))
        ->assertSessionHasErrors('cv');
});

test('ats scoring stores a compatibility result', function () {
    $user = User::factory()->jobSeeker()->create();
    subscribeToPlan($user, [
        'mock_interviews_per_month' => null,
        'cv_documents' => null,
        'ats' => true,
    ], [
        'name' => 'professional',
        'display_name' => 'Premium Plan',
        'amount' => 19.99,
        'target_role' => 'job_seeker',
    ]);
    CvDocument::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('ats-scoring.store'), [
            'job_description' => str_repeat('We need PHP Laravel Vue Docker experience. ', 4),
        ])
        ->assertRedirect(route('ats-scoring'));

    expect($user->atsAnalyses()->count())->toBe(1)
        ->and($user->atsAnalyses()->first()->score)->toBeInt();
});
