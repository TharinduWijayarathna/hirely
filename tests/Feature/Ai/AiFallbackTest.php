<?php

use App\Models\MockInterviewSession;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    app()->forgetInstance(AIService::class);
});

test('question generation falls back to the default bank when gemini is unset', function () {
    config(['services.gemini.api_key' => '']);

    $questions = (new AIService)->generateQuestions('technical', 'intermediate', 3);

    expect($questions)->toHaveCount(3)
        ->and($questions[0])->toContain('REST');
});

test('configured interview questions fall back by category when gemini is unset', function () {
    config(['services.gemini.api_key' => '']);

    $questions = (new AIService)->generateConfiguredQuestions(
        'intermediate',
        ['technical' => 2, 'cv' => 1],
        'Backend Engineer',
        'Laravel APIs',
        'Built Vue dashboards',
        ['Problem solving'],
    );

    expect($questions)->toHaveCount(3)
        ->and(collect($questions)->pluck('category')->all())->toBe(['technical', 'technical', 'cv'])
        ->and($questions[2]['text'])->toContain('project');
});

test('interview evaluation uses the heuristic fallback when the gemini request fails', function () {
    config(['services.gemini.api_key' => 'gemini-test']);
    Http::fake(['*' => Http::response(['error' => 'unavailable'], 500)]);

    $evaluation = (new AIService)->evaluateInterview(
        [['category' => 'technical', 'text' => 'What is REST?']],
        ['What is REST?' => 'Representational state transfer with resources and verbs.'],
        'intermediate',
        ['Technical depth', 'Communication'],
        'Backend Engineer',
    );

    expect($evaluation['overall_score'])->toBeInt()
        ->and($evaluation['overall_score'])->toBeGreaterThan(40)
        ->and($evaluation['confidence'])->toBe(0.35)
        ->and($evaluation['rationale'])->toContain('without the AI provider')
        ->and($evaluation['strengths'])->toBeArray()
        ->and($evaluation['weaknesses'])->toBeArray()
        ->and($evaluation['dimensions'])->toHaveCount(2)
        ->and($evaluation['answers'][0]['evidence'])->toContain('Representational');
});

test('cv analysis fails without gemini instead of parsing the file locally', function () {
    config(['services.gemini.api_key' => '']);

    expect(fn () => (new AIService)->analyzeCurriculumVitae('%PDF-1.4 fake', 'application/pdf'))
        ->toThrow(\RuntimeException::class);
});

test('ats scoring falls back to heuristics when gemini is unset', function () {
    config(['services.gemini.api_key' => '']);

    $result = (new AIService)->scoreAtsCompatibility(
        'Looking for PHP Laravel Vue experience',
        ['skills' => ['PHP', 'Laravel', 'Vue']],
    );

    expect($result['score'])->toBeInt()
        ->and($result['analysis']['matched_skills'] ?? [])->not->toBeEmpty();
});

test('mock interviews still start with fallback questions when gemini is unset', function () {
    config(['services.gemini.api_key' => '']);
    $seeker = User::factory()->jobSeeker()->create();

    $this->actingAs($seeker)
        ->post(route('mock-interview.store'), [
            'type' => 'technical',
            'difficulty' => 'intermediate',
            'mode' => 'text',
        ])
        ->assertRedirect();

    $session = MockInterviewSession::where('user_id', $seeker->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->questions)->toBeArray()
        ->and($session->questions[0])->toContain('REST');
});

test('follow-up generation falls back to a heuristic probe when gemini is unset', function () {
    config(['services.gemini.api_key' => '']);

    $followUp = (new AIService)->generateFollowUpQuestion(
        'What is REST?',
        'HTTP APIs.',
        'intermediate',
        'Backend Engineer',
    );

    expect($followUp)->toBe('Can you give a specific example that supports your answer?');
});

test('follow-up generation stays silent for a complete answer when gemini is unset', function () {
    config(['services.gemini.api_key' => '']);

    $followUp = (new AIService)->generateFollowUpQuestion(
        'What is REST?',
        'REST is representational state transfer. Resources are identified by URLs and clients use HTTP verbs to read and update them.',
        'intermediate',
        'Backend Engineer',
    );

    expect($followUp)->toBeNull();
});

test('mock text interviews can insert a follow-up question', function () {
    config(['services.gemini.api_key' => '']);
    $seeker = User::factory()->jobSeeker()->create();
    $session = MockInterviewSession::create([
        'user_id' => $seeker->id,
        'type' => 'technical',
        'difficulty' => 'intermediate',
        'mode' => 'text',
        'status' => 'in_progress',
        'questions' => ['What is REST?', 'Explain SQL joins.'],
    ]);

    $this->actingAs($seeker)
        ->post(route('mock-interview.follow-up', $session), [
            'question' => 'What is REST?',
            'answer' => 'HTTP APIs.',
            'answers' => ['What is REST?' => 'HTTP APIs.'],
        ])
        ->assertRedirect(route('mock-interview.session', $session));

    $questions = $session->fresh()->questions;

    expect($questions)->toHaveCount(3)
        ->and($questions[1]['follow_up'] ?? false)->toBeTrue()
        ->and($questions[2])->toBe('Explain SQL joins.');
});
