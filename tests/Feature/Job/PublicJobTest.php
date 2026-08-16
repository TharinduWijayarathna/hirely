<?php

use App\Models\Company;
use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;

beforeEach(function () {
    config(['services.gemini.api_key' => '']);
});

test('the homepage lists live jobs', function () {
    $visible = Job::factory()->create([
        'title' => 'Homepage Visible Role',
        'status' => 'active',
    ]);
    Job::factory()->create([
        'title' => 'Hidden Draft Role',
        'status' => 'draft',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('jobs', 1)
            ->where('jobs.0.id', $visible->id)
            ->where('jobs.0.title', 'Homepage Visible Role')
            ->where('jobCount', 1)
        );
});

test('the public jobs board lists only active unexpired postings', function () {
    $company = Company::factory()->create(['name' => 'Hirely Labs', 'slug' => 'hirely-labs']);
    $hr = User::factory()->hrProfessional($company->id)->create();
    $visible = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
        'title' => 'Public Laravel Role',
        'status' => 'active',
    ]);
    Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
        'title' => 'Draft Role',
        'status' => 'draft',
    ]);
    Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
        'title' => 'Expired Role',
        'status' => 'active',
        'expires_at' => now()->subDay(),
    ]);

    $this->get(route('jobs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/Jobs')
            ->has('jobs.data', 1)
            ->where('jobs.data.0.id', $visible->id)
            ->where('jobs.data.0.title', 'Public Laravel Role')
        );
});

test('a shareable job page is available by slug', function () {
    $job = Job::factory()->create([
        'title' => 'Staff Engineer',
        'status' => 'active',
    ]);

    expect($job->slug)->not->toBeEmpty();

    $this->get(route('jobs.show', $job))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/JobShow')
            ->where('job.title', 'Staff Engineer')
            ->where('share_url', $job->publicUrl())
            ->where('can_apply', false)
        );
});

test('draft jobs are not reachable on the public apply page', function () {
    $job = Job::factory()->create([
        'status' => 'draft',
        'slug' => 'secret-draft-role',
    ]);

    $this->get(route('jobs.show', $job))->assertNotFound();
});

test('guests are sent to login when they open the apply link', function () {
    $job = Job::factory()->create(['status' => 'active']);

    $this->get(route('jobs.apply', $job))
        ->assertRedirect(route('login'));
});

test('job seekers can apply once from the shareable link', function () {
    $job = Job::factory()->create(['status' => 'active']);
    $seeker = User::factory()->jobSeeker()->create();

    $this->actingAs($seeker)
        ->post(route('jobs.apply.store', $job), [
            'cover_letter' => 'I want this role.',
        ])
        ->assertRedirect(route('jobs.show', $job));

    $application = JobApplication::where('user_id', $seeker->id)->where('job_id', $job->id)->first();

    expect($application)->not->toBeNull()
        ->and($application->cover_letter)->toContain('I want this role');

    $this->actingAs($seeker)
        ->post(route('jobs.apply.store', $job))
        ->assertSessionHasErrors('job_id');
});

test('applying assigns an interview when the company has an active template', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    InterviewTemplate::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
        'job_id' => $job->id,
        'is_active' => true,
    ]);
    $seeker = User::factory()->jobSeeker()->create();

    $response = $this->actingAs($seeker)
        ->post(route('jobs.apply.store', $job), [
            'cover_letter' => 'Ready for the interview.',
        ]);

    $interview = Interview::where('candidate_id', $seeker->id)->where('job_id', $job->id)->first();

    expect($interview)->not->toBeNull()
        ->and($interview->assigned_by)->toBe($hr->id)
        ->and($interview->status)->toBe('pending');

    $response->assertRedirect(route('interviews.show', $interview));
});

test('hr without an organization cannot post jobs', function () {
    $hr = User::factory()->hrProfessional()->create();

    $this->actingAs($hr)
        ->post(route('post-jobs.store'), jobListingPayload())
        ->assertRedirect(route('post-jobs'))
        ->assertSessionHasErrors('company');

    expect(Job::count())->toBe(0);
});
