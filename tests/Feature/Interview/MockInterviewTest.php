<?php

use App\Models\CvDocument;
use App\Models\MockInterviewSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('mock interviews require a processed cv', function () {
    $seeker = User::factory()->jobSeeker()->create();

    $this->actingAs($seeker)
        ->post(route('mock-interview.store'), [
            'type' => 'technical',
            'difficulty' => 'intermediate',
            'mode' => 'text',
        ])
        ->assertRedirect(route('mock-interview'))
        ->assertSessionHasErrors('cv');
});

test('mock interviews generate ten categorized cv-based questions in one request', function () {
    config(['services.gemini.api_key' => 'gemini-test']);
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'technical' => [
                                'How did you use Laravel at Acme?',
                                'How do you structure Vue with Laravel APIs?',
                                'What Docker setup did you use for Hirely?',
                                'How would you test a Laravel endpoint you shipped at Acme?',
                            ],
                            'behavioral' => [
                                'How did you handle disagreement on the Hirely project?',
                                'Tell me about a tight deadline as an Engineer at Acme.',
                            ],
                            'scenario' => [
                                'A Laravel API at Acme is failing in production. What do you do?',
                                'How would you roll out a breaking change on Hirely?',
                            ],
                            'cv' => [
                                'Walk me through Hirely and your role at Acme.',
                                'Which PHP skill on your CV was hardest to develop?',
                            ],
                        ]),
                    ]],
                ],
            ]],
        ]),
    ]);

    $seeker = User::factory()->jobSeeker()->create();
    CvDocument::factory()->create(['user_id' => $seeker->id]);

    $this->actingAs($seeker)
        ->post(route('mock-interview.store'), [
            'type' => 'mixed',
            'difficulty' => 'intermediate',
            'mode' => 'voice',
        ])
        ->assertRedirect();

    $session = MockInterviewSession::where('user_id', $seeker->id)->first();
    $categories = collect($session->questions)->pluck('category');

    expect($session)->not->toBeNull()
        ->and($session->questions)->toHaveCount(10)
        ->and($categories->unique()->sort()->values()->all())->toBe(['behavioral', 'cv', 'scenario', 'technical'])
        ->and($categories->filter(fn ($category) => $category === 'cv')->count())->toBe(2);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'generateContent')
        && str_contains($request->body(), 'Laravel')
        && str_contains($request->body(), 'Acme')
        && str_contains($request->body(), 'Hirely')
        && str_contains($request->body(), 'Engineer')
        && ! str_contains($request->body(), 'inlineData'));
});

test('completed mock interviews open a dedicated results page', function () {
    $seeker = User::factory()->jobSeeker()->create();
    $session = MockInterviewSession::create([
        'user_id' => $seeker->id,
        'type' => 'mixed',
        'difficulty' => 'intermediate',
        'mode' => 'text',
        'status' => 'completed',
        'score' => 72,
        'completed_at' => now(),
        'answers' => ['What is REST?' => 'Representational state transfer'],
        'evaluation' => [
            'overall_score' => 72,
            'rationale' => 'Solid REST knowledge.',
            'answers' => [
                [
                    'question' => 'What is REST?',
                    'category' => 'technical',
                    'score' => 72,
                    'feedback' => 'Good answer.',
                    'answer' => 'Representational state transfer',
                ],
            ],
        ],
    ]);

    $this->actingAs($seeker)
        ->get(route('mock-interview.results', $session))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('job-seeker/MockInterviewResult')
            ->where('session.id', $session->id)
            ->where('session.score', 72)
            ->has('session.evaluation.answers', 1)
        );

    $this->actingAs($seeker)
        ->get(route('mock-interview.session', $session))
        ->assertRedirect(route('mock-interview.results', $session));
});
