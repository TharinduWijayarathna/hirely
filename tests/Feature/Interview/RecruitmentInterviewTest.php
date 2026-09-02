<?php

use App\Models\Company;
use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.gemini.api_key' => '']);
});

test('hr can create an interview template with a valid question mix', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();

    $this->actingAs($hr)
        ->post(route('interview-templates.store'), [
            'name' => 'Backend screen',
            'question_count' => 5,
            'duration_minutes' => 30,
            'difficulty' => 'intermediate',
            'mode' => 'text',
            'technical_percentage' => 40,
            'behavioral_percentage' => 30,
            'scenario_percentage' => 20,
            'cv_percentage' => 10,
            'evaluation_criteria' => ['Problem solving'],
            'question_weights' => ['Problem solving' => 100],
            'is_active' => true,
        ])
        ->assertRedirect(route('interview-templates'));

    expect(InterviewTemplate::where('name', 'Backend screen')->first())
        ->question_weights->toMatchArray(['Problem solving' => 100]);
});

test('hr cannot create a template whose mix does not add up to 100', function () {
    $hr = User::factory()->hrProfessional()->create();

    $this->actingAs($hr)
        ->post(route('interview-templates.store'), [
            'name' => 'Bad mix',
            'question_count' => 5,
            'difficulty' => 'intermediate',
            'mode' => 'text',
            'technical_percentage' => 50,
            'behavioral_percentage' => 50,
            'scenario_percentage' => 50,
            'cv_percentage' => 50,
        ])
        ->assertSessionHasErrors('technical_percentage');
});

test('hr can assign a recruitment interview to an applicant', function () {
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
    $template = InterviewTemplate::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
        'evaluation_criteria' => ['Technical depth', 'Role fit'],
    ]);

    $this->actingAs($hr)
        ->post(route('review-candidates.interviews.store', $application), [
            'interview_template_id' => $template->id,
        ])
        ->assertRedirect(route('review-candidates'));

    $interview = Interview::where('job_application_id', $application->id)->first();

    expect($interview)->not->toBeNull()
        ->and($interview->candidate_id)->toBe($candidate->id)
        ->and($interview->questions)->not->toBeEmpty()
        ->and($interview->questions)->toHaveCount(10)
        ->and($interview->criteria)->toBe(['Technical depth', 'Role fit'])
        ->and($interview->question_weights['Technical depth'] ?? null)->toBe(40)
        ->and($interview->mode)->toBe('voice');
});

test('candidates can complete an assigned interview', function () {
    fakeGeminiInterviewEvaluation([
        'overall_score' => 78,
        'rationale' => 'Clear REST explanation.',
        'answers' => [
            ['question' => 'What is REST?', 'category' => 'technical', 'score' => 78, 'feedback' => 'Good answer.', 'evidence' => 'Representational state transfer'],
        ],
    ]);

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
        'status' => 'shortlisted',
    ]);
    $interview = Interview::factory()->create([
        'interview_template_id' => InterviewTemplate::factory()->create([
            'user_id' => $hr->id,
            'company_id' => $company->id,
        ])->id,
        'job_application_id' => $application->id,
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(10),
        'criteria' => ['Technical depth', 'Communication'],
        'questions' => [
            ['category' => 'technical', 'text' => 'What is REST?'],
        ],
    ]);

    $this->actingAs($candidate)
        ->put(route('interviews.update', $interview), [
            'answers' => ['What is REST?' => 'Representational state transfer'],
            'status' => 'completed',
        ])
        ->assertRedirect(route('interviews.show', $interview));

    $interview = $interview->fresh();

    expect($interview->status)->toBe('completed')
        ->and($application->fresh()->status)->toBe('interviewed')
        ->and($interview->review_status)->toBe(Interview::REVIEW_PENDING)
        ->and($interview->evaluation)->not->toBeEmpty()
        ->and($interview->evaluation['strengths'] ?? null)->toBeArray()
        ->and($interview->evaluation['weaknesses'] ?? null)->toBeArray()
        ->and($interview->evaluation['dimensions'] ?? null)->toBeArray()
        ->and($interview->evaluation['answers'][0]['answer'] ?? null)->toBe('Representational state transfer')
        ->and($interview->ai_score)->not->toBeNull()
        ->and((float) $interview->score)->toBe((float) $interview->ai_score);
});

test('hr cannot view another company job posting', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $hrA = User::factory()->hrProfessional($companyA->id)->create();
    $hrB = User::factory()->hrProfessional($companyB->id)->create();
    $job = Job::factory()->create([
        'user_id' => $hrB->id,
        'company_id' => $companyB->id,
    ]);

    $this->actingAs($hrA)
        ->put(route('post-jobs.update', $job), [
            'title' => 'Hacked title',
            'description' => 'Should not work',
            'type' => 'full_time',
            'remote' => 'on_site',
            'status' => 'active',
        ])
        ->assertForbidden();
});

test('completed interviews show a result page to the candidate without hr notes', function () {
    ['candidate' => $candidate, 'interview' => $interview] = completedInterview([
        'human_notes' => 'Internal calibration note',
    ]);

    $this->actingAs($candidate)
        ->get(route('interviews.show', $interview))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('job-seeker/InterviewResult')
            ->where('interview.id', $interview->id)
            ->where('interview.score', 72)
            ->missing('interview.human_notes')
            ->missing('interview.review_audit')
        );
});

test('hr can view completed interview results for their company', function () {
    ['hr' => $hr, 'interview' => $interview] = completedInterview();

    $this->actingAs($hr)
        ->get(route('interview-results.show', $interview))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hr/InterviewResult')
            ->where('interview.id', $interview->id)
            ->has('interview.evaluation.dimensions')
        );
});

test('hr from another company cannot view or review interview results', function () {
    ['interview' => $interview] = completedInterview();
    $otherHr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();

    $this->actingAs($otherHr)
        ->get(route('interview-results.show', $interview))
        ->assertForbidden();

    $this->actingAs($otherHr)
        ->put(route('interview-results.review', $interview), [
            'action' => 'accepted',
            'human_notes' => 'This should not be allowed to succeed.',
        ])
        ->assertForbidden();
});

test('hr can accept an ai interview score with notes', function () {
    ['hr' => $hr, 'interview' => $interview] = completedInterview();

    $this->actingAs($hr)
        ->put(route('interview-results.review', $interview), [
            'action' => 'accepted',
            'human_notes' => 'AI score matches the transcript and criteria.',
        ])
        ->assertRedirect(route('interview-results.show', $interview));

    $interview = $interview->fresh();

    expect($interview->review_status)->toBe(Interview::REVIEW_ACCEPTED)
        ->and((float) $interview->score)->toBe(72.0)
        ->and($interview->human_notes)->toContain('AI score matches')
        ->and($interview->review_audit)->toHaveCount(1)
        ->and($interview->review_audit[0]['action'])->toBe('accepted');
});

test('hr can edit an ai interview score and the effective score changes', function () {
    ['hr' => $hr, 'interview' => $interview] = completedInterview();

    $this->actingAs($hr)
        ->put(route('interview-results.review', $interview), [
            'action' => 'edited',
            'human_score' => 91,
            'human_notes' => 'Raised for strong system-design examples in the answers.',
        ])
        ->assertRedirect(route('interview-results.show', $interview));

    $interview = $interview->fresh();

    expect($interview->review_status)->toBe(Interview::REVIEW_EDITED)
        ->and((float) $interview->human_score)->toBe(91.0)
        ->and((float) $interview->score)->toBe(91.0)
        ->and((float) $interview->ai_score)->toBe(72.0);
});

test('hr can reject an ai interview score and a note is required', function () {
    ['hr' => $hr, 'interview' => $interview] = completedInterview();

    $this->actingAs($hr)
        ->put(route('interview-results.review', $interview), [
            'action' => 'rejected',
            'human_notes' => 'short',
        ])
        ->assertSessionHasErrors('human_notes');

    $this->actingAs($hr)
        ->put(route('interview-results.review', $interview), [
            'action' => 'rejected',
            'human_notes' => 'Answers appear copied and should not be used for ranking.',
        ])
        ->assertRedirect(route('interview-results.show', $interview));

    expect($interview->fresh()->review_status)->toBe(Interview::REVIEW_REJECTED);
});

test('job seekers cannot open hr interview results', function () {
    ['candidate' => $candidate, 'interview' => $interview] = completedInterview();

    $this->actingAs($candidate)
        ->get(route('interview-results.show', $interview))
        ->assertForbidden();
});

test('criterion weights are snapshotted and applied to the overall score', function () {
    fakeGeminiInterviewEvaluation([
        'dimensions' => [
            ['name' => 'Technical depth', 'score' => 90, 'weight' => 1, 'evidence' => 'Representational state transfer', 'comment' => 'Strong'],
            ['name' => 'Communication', 'score' => 50, 'weight' => 1, 'evidence' => 'Representational state transfer', 'comment' => 'Basic'],
        ],
    ]);

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
        'status' => 'shortlisted',
    ]);
    $interview = Interview::factory()->create([
        'interview_template_id' => InterviewTemplate::factory()->create([
            'user_id' => $hr->id,
            'company_id' => $company->id,
        ])->id,
        'job_application_id' => $application->id,
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(8),
        'criteria' => ['Technical depth', 'Communication'],
        'question_weights' => [
            'Technical depth' => 80,
            'Communication' => 20,
        ],
        'questions' => [
            ['category' => 'technical', 'text' => 'What is REST?'],
        ],
    ]);

    $this->actingAs($candidate)
        ->put(route('interviews.update', $interview), [
            'answers' => ['What is REST?' => 'Representational state transfer'],
            'status' => 'completed',
        ])
        ->assertRedirect(route('interviews.show', $interview));

    $evaluation = $interview->fresh()->evaluation;

    expect($evaluation['dimensions'][0]['weight'])->toBe(80)
        ->and($evaluation['dimensions'][1]['weight'])->toBe(20)
        ->and($evaluation['overall_score'])->toBeInt();
});

test('text interviews can insert a follow-up after the answered question', function () {
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
        ])->id,
        'job_application_id' => $application->id,
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'status' => 'in_progress',
        'questions' => [
            ['category' => 'technical', 'text' => 'What is REST?'],
            ['category' => 'behavioral', 'text' => 'Tell me about a challenge.'],
        ],
    ]);

    $this->actingAs($candidate)
        ->post(route('interviews.follow-up', $interview), [
            'question' => 'What is REST?',
            'answer' => 'APIs over HTTP.',
            'answers' => ['What is REST?' => 'APIs over HTTP.'],
        ])
        ->assertRedirect(route('interviews.show', $interview));

    $questions = $interview->fresh()->questions;

    expect($questions)->toHaveCount(3)
        ->and($questions[1]['follow_up'] ?? false)->toBeTrue()
        ->and($questions[1]['text'])->toBeString()
        ->and($questions[2]['text'])->toBe('Tell me about a challenge.');
});

test('a complete answer does not insert a clarification question', function () {
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
        ])->id,
        'job_application_id' => $application->id,
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'status' => 'in_progress',
        'questions' => [
            ['category' => 'technical', 'text' => 'What is REST?'],
            ['category' => 'behavioral', 'text' => 'Tell me about a challenge.'],
        ],
    ]);

    $this->actingAs($candidate)
        ->post(route('interviews.follow-up', $interview), [
            'question' => 'What is REST?',
            'answer' => 'REST is representational state transfer. Clients identify resources with URLs and use HTTP verbs to read and update them in a stateless way.',
            'answers' => [
                'What is REST?' => 'REST is representational state transfer. Clients identify resources with URLs and use HTTP verbs to read and update them in a stateless way.',
            ],
        ])
        ->assertRedirect(route('interviews.show', $interview));

    expect($interview->fresh()->questions)->toHaveCount(2);
});

test('recruitment interviews cap follow-ups at three', function () {
    ['candidate' => $candidate, 'interview' => $interview] = completedInterview([
        'status' => 'in_progress',
        'completed_at' => null,
        'ai_score' => null,
        'score' => null,
        'review_status' => null,
        'evaluation' => null,
        'questions' => [
            ['category' => 'technical', 'text' => 'What is REST?'],
            ['category' => 'follow_up', 'text' => 'Can you give an example?', 'follow_up' => true],
            ['category' => 'follow_up', 'text' => 'What was the outcome?', 'follow_up' => true],
            ['category' => 'follow_up', 'text' => 'How did you measure success?', 'follow_up' => true],
        ],
    ]);

    $this->actingAs($candidate)
        ->post(route('interviews.follow-up', $interview), [
            'question' => 'What is REST?',
            'answer' => 'A short answer.',
        ])
        ->assertRedirect(route('interviews.show', $interview));

    expect($interview->fresh()->questions)->toHaveCount(4);
});

test('voice recruitment interviews render the spoken session page', function () {
    $this->withoutVite();
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
        'status' => 'pending',
    ]);

    $this->actingAs($candidate)
        ->get(route('interviews.show', $interview))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('job-seeker/InterviewSessionVoice'));
});

test('assigned interviews open the voice assistant with the question script', function () {
    $this->withoutVite();
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
            'mode' => 'text',
        ])->id,
        'job_application_id' => $application->id,
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'mode' => 'text',
        'status' => 'pending',
        'questions' => [
            ['category' => 'technical', 'text' => 'What is REST?'],
            ['category' => 'behavioral', 'text' => 'Tell me about a challenge.'],
        ],
    ]);

    $this->actingAs($candidate)
        ->get(route('interviews.show', $interview))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('job-seeker/InterviewSessionVoice')
            ->where('interview.questions.0.text', 'What is REST?')
            ->has('interview.questions', 2)
        );
});

test('voice recruitment interviews store conversation turns and complete from history', function () {
    fakeGeminiInterviewEvaluation();

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
        'status' => 'shortlisted',
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
        'started_at' => now()->subMinutes(12),
        'questions' => [],
    ]);

    $this->actingAs($candidate)
        ->get(route('interviews.initial', $interview))
        ->assertRedirect(route('interviews.show', $interview));

    expect($interview->fresh()->conversation_history)->not->toBeEmpty();

    $this->actingAs($candidate)
        ->post(route('interviews.conversation', $interview), [
            'user_message' => 'I have built Laravel APIs for four years.',
        ])
        ->assertRedirect(route('interviews.show', $interview));

    $this->actingAs($candidate)
        ->put(route('interviews.update', $interview), [
            'status' => 'completed',
        ])
        ->assertRedirect(route('interviews.show', $interview));

    $interview = $interview->fresh();

    expect($interview->status)->toBe('completed')
        ->and($interview->evaluation)->not->toBeEmpty()
        ->and($application->fresh()->status)->toBe('interviewed');
});

/**
 * @param  array<string, mixed>  $overrides
 */
function fakeGeminiInterviewEvaluation(array $overrides = []): void
{
    config(['services.gemini.api_key' => 'gemini-test']);

    $payload = array_merge([
        'overall_score' => 78,
        'rationale' => 'Solid interview performance.',
        'confidence' => 0.82,
        'strengths' => ['Clear communication'],
        'weaknesses' => ['Could add more examples'],
        'dimensions' => [
            ['name' => 'Technical depth', 'score' => 80, 'weight' => 1, 'evidence' => 'Representational state transfer', 'comment' => 'Solid'],
            ['name' => 'Communication', 'score' => 75, 'weight' => 1, 'evidence' => 'Representational state transfer', 'comment' => 'Clear'],
        ],
        'answers' => [
            ['question' => 'What is REST?', 'category' => 'technical', 'score' => 78, 'feedback' => 'Good answer.', 'evidence' => 'Representational state transfer'],
        ],
    ], $overrides);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode($payload),
                    ]],
                ],
            ]],
        ]),
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array{company: Company, hr: User, job: Job, candidate: User, application: JobApplication, interview: Interview}
 */
function completedInterview(array $overrides = []): array
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
        'status' => 'interviewed',
    ]);
    $interview = Interview::factory()->create(array_merge([
        'interview_template_id' => InterviewTemplate::factory()->create([
            'user_id' => $hr->id,
            'company_id' => $company->id,
        ])->id,
        'job_application_id' => $application->id,
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'status' => 'completed',
        'completed_at' => now(),
        'ai_score' => 72,
        'score' => 72,
        'review_status' => Interview::REVIEW_PENDING,
        'evaluation' => [
            'overall_score' => 72,
            'rationale' => 'Solid REST knowledge.',
            'confidence' => 0.4,
            'strengths' => ['Clear definition'],
            'weaknesses' => ['Needs examples'],
            'dimensions' => [
                ['name' => 'Technical depth', 'score' => 72, 'weight' => 1, 'evidence' => 'REST', 'comment' => 'OK'],
            ],
            'answers' => [
                ['question' => 'What is REST?', 'category' => 'technical', 'score' => 72, 'feedback' => 'Good', 'evidence' => 'transfer'],
            ],
        ],
    ], $overrides));

    return compact('company', 'hr', 'job', 'candidate', 'application', 'interview');
}
