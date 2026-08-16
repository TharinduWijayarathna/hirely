<?php

use App\Models\AtsAnalysis;
use App\Models\Company;
use App\Models\CvDocument;
use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;

function rankingSetup(): array
{
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
        'title' => 'Backend Engineer',
    ]);

    return compact('company', 'hr', 'job');
}

function applyForJob(Job $job, string $name, array $overrides = []): array
{
    $candidate = User::factory()->jobSeeker()->create(['name' => $name]);
    $cv = CvDocument::factory()->create([
        'user_id' => $candidate->id,
        'review_score' => $overrides['review_score'] ?? 70,
    ]);
    $application = JobApplication::factory()->create([
        'user_id' => $candidate->id,
        'job_id' => $job->id,
        'cv_document_id' => $cv->id,
        'status' => $overrides['status'] ?? 'interviewed',
        'cover_letter' => 'I would like this role.',
        'applied_at' => $overrides['applied_at'] ?? now(),
    ]);

    if (array_key_exists('interview_score', $overrides)) {
        Interview::factory()->create([
            'interview_template_id' => InterviewTemplate::factory()->create([
                'user_id' => $job->user_id,
                'company_id' => $job->company_id,
            ])->id,
            'job_application_id' => $application->id,
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'status' => 'completed',
            'completed_at' => now(),
            'score' => $overrides['interview_score'],
            'ai_score' => $overrides['interview_score'],
            'review_status' => $overrides['review_status'] ?? Interview::REVIEW_ACCEPTED,
            'evaluation' => [
                'overall_score' => $overrides['interview_score'],
                'strengths' => ['Clear examples'],
                'weaknesses' => ['Could be more concise'],
                'dimensions' => [
                    ['name' => 'Technical depth', 'score' => $overrides['interview_score'], 'evidence' => 'REST', 'comment' => 'Solid'],
                    ['name' => 'Communication', 'score' => max(40, $overrides['interview_score'] - 5), 'evidence' => 'Clear', 'comment' => 'OK'],
                ],
            ],
        ]);
    }

    return compact('candidate', 'cv', 'application');
}

test('hr rankings order applicants by weighted interview cv and application scores', function () {
    ['hr' => $hr, 'job' => $job] = rankingSetup();
    $strong = applyForJob($job, 'Strong Candidate', [
        'interview_score' => 90,
        'review_score' => 80,
    ]);
    $weak = applyForJob($job, 'Weaker Candidate', [
        'interview_score' => 50,
        'review_score' => 40,
    ]);

    $this->actingAs($hr)
        ->get(route('rankings', ['job_id' => $job->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hr/Rankings')
            ->where('rankings.0.candidate.name', 'Strong Candidate')
            ->where('rankings.1.candidate.name', 'Weaker Candidate')
            ->where('rankings.0.position', 1)
        );

    expect((int) $strong['application']->fresh()->ranking_position)->toBe(1)
        ->and((int) $weak['application']->fresh()->ranking_position)->toBe(2)
        ->and((float) $strong['application']->fresh()->ranking_score)
        ->toBeGreaterThan((float) $weak['application']->fresh()->ranking_score);
});

test('rejected interview scores are excluded from ranking', function () {
    ['hr' => $hr, 'job' => $job] = rankingSetup();
    applyForJob($job, 'Rejected Interview', [
        'interview_score' => 99,
        'review_status' => Interview::REVIEW_REJECTED,
        'review_score' => 50,
        'status' => 'interviewed',
    ]);
    applyForJob($job, 'Accepted Interview', [
        'interview_score' => 60,
        'review_status' => Interview::REVIEW_ACCEPTED,
        'review_score' => 50,
        'status' => 'interviewed',
    ]);

    $this->actingAs($hr)
        ->get(route('rankings', ['job_id' => $job->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hr/Rankings')
            ->where('rankings.0.candidate.name', 'Accepted Interview')
            ->where('rankings.1.signals.interview.status', 'rejected')
        );
});

test('ats score for the job is preferred over generic cv review', function () {
    ['hr' => $hr, 'job' => $job] = rankingSetup();
    $match = applyForJob($job, 'ATS Match', [
        'review_score' => 20,
        'status' => 'pending',
    ]);
    applyForJob($job, 'CV Only', [
        'review_score' => 90,
        'status' => 'pending',
    ]);

    AtsAnalysis::create([
        'user_id' => $match['candidate']->id,
        'cv_document_id' => $match['cv']->id,
        'job_id' => $job->id,
        'job_description' => $job->description,
        'score' => 95,
        'analysis' => ['matched_skills' => ['Laravel']],
    ]);

    $this->actingAs($hr)
        ->get(route('rankings', ['job_id' => $job->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hr/Rankings')
            ->where('rankings.0.candidate.name', 'ATS Match')
            ->where('rankings.0.signals.cv.source', 'ats')
        );
});

test('hr can compare two applicants for the same job', function () {
    ['hr' => $hr, 'job' => $job] = rankingSetup();
    $first = applyForJob($job, 'Ada', ['interview_score' => 88, 'review_score' => 70]);
    $second = applyForJob($job, 'Ben', ['interview_score' => 70, 'review_score' => 60]);

    $this->actingAs($hr)
        ->get(route('rankings.compare', [
            'job' => $job,
            'applications' => [$first['application']->id, $second['application']->id],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hr/CandidateComparison')
            ->has('candidates', 2)
            ->where('candidates.0.candidate.name', 'Ada')
            ->where('criteria.0', 'Technical depth')
            ->has('candidates.0.dimensions.Technical depth.score')
        );
});

test('comparison requires at least two applications from the same job', function () {
    ['hr' => $hr, 'job' => $job] = rankingSetup();
    $only = applyForJob($job, 'Solo', ['interview_score' => 80]);

    $this->actingAs($hr)
        ->get(route('rankings.compare', [
            'job' => $job,
            'applications' => [$only['application']->id],
        ]))
        ->assertSessionHasErrors('applications');
});

test('hr cannot rank or compare another company job', function () {
    ['job' => $job] = rankingSetup();
    $first = applyForJob($job, 'Ada', ['interview_score' => 80]);
    $second = applyForJob($job, 'Ben', ['interview_score' => 70]);
    $otherHr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();

    $this->actingAs($otherHr)
        ->get(route('rankings', ['job_id' => $job->id]))
        ->assertNotFound();

    $this->actingAs($otherHr)
        ->get(route('rankings.compare', [
            'job' => $job,
            'applications' => [$first['application']->id, $second['application']->id],
        ]))
        ->assertForbidden();
});
