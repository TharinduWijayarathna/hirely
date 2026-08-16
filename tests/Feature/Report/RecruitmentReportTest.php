<?php

use App\Models\Company;
use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;

test('hr reports include funnel and interview volume for visible jobs', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    subscribeToPlan($hr, ['jobs' => null, 'reports' => true]);
    $job = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
    ]);
    $candidate = User::factory()->jobSeeker()->create();
    $application = JobApplication::factory()->create([
        'user_id' => $candidate->id,
        'job_id' => $job->id,
        'status' => 'interviewed',
        'ranking_score' => 81,
    ]);
    Interview::factory()->create([
        'interview_template_id' => InterviewTemplate::factory()->create([
            'user_id' => $hr->id,
            'company_id' => $company->id,
        ])->id,
        'job_application_id' => $application->id,
        'job_id' => $job->id,
        'candidate_id' => $candidate->id,
        'status' => 'completed',
        'completed_at' => now(),
        'score' => 84,
        'review_status' => Interview::REVIEW_ACCEPTED,
        'duration_minutes' => 25,
    ]);

    $this->actingAs($hr)
        ->get(route('reports', ['job_id' => $job->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('hr/Reports')
            ->where('interview_volume.completed', 1)
            ->where('interview_volume.avg_score', 84)
            ->where('funnel.3.status', 'interviewed')
            ->where('funnel.3.count', 1)
        );
});

test('job seekers cannot open recruitment reports', function () {
    $this->actingAs(User::factory()->jobSeeker()->create())
        ->get(route('reports'))
        ->assertForbidden();
});

test('hr cannot open reports for another company job', function () {
    $job = Job::factory()->create();
    $hr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();
    subscribeToPlan($hr, ['jobs' => null, 'reports' => true]);

    $this->actingAs($hr)
        ->get(route('reports', ['job_id' => $job->id]))
        ->assertNotFound();
});

test('hr can export recruitment reports as csv', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    subscribeToPlan($hr, ['jobs' => null, 'reports' => true]);
    $job = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
    ]);
    JobApplication::factory()->create([
        'job_id' => $job->id,
        'status' => 'interviewed',
        'ranking_score' => 81,
    ]);

    $response = $this->actingAs($hr)
        ->get(route('reports.export', ['job_id' => $job->id]));

    $response->assertOk()
        ->assertDownload('hirely-reports-'.now()->format('Y-m-d').'.csv');

    expect($response->streamedContent())
        ->toContain('funnel')
        ->toContain('interviewed');
});

test('job seekers cannot export recruitment reports', function () {
    $this->actingAs(User::factory()->jobSeeker()->create())
        ->get(route('reports.export'))
        ->assertForbidden();
});
