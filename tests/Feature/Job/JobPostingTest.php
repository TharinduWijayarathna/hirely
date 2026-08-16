<?php

use App\Models\Company;
use App\Models\Job;
use App\Models\User;

test('hr can create update and delete a job posting', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();

    $this->actingAs($hr)
        ->post(route('post-jobs.store'), jobListingPayload([
            'title' => 'Platform Engineer',
            'location' => 'Colombo',
        ]))
        ->assertRedirect(route('post-jobs'));

    $job = Job::where('title', 'Platform Engineer')->first();

    expect($job)->not->toBeNull()
        ->and($job->user_id)->toBe($hr->id)
        ->and($job->company_id)->toBe($company->id);

    $this->actingAs($hr)
        ->put(route('post-jobs.update', $job), jobListingPayload([
            'title' => 'Staff Platform Engineer',
            'status' => 'closed',
        ]))
        ->assertRedirect(route('post-jobs'));

    expect($job->fresh()->title)->toBe('Staff Platform Engineer')
        ->and($job->fresh()->status)->toBe('closed');

    $this->actingAs($hr)
        ->delete(route('post-jobs.destroy', $job))
        ->assertRedirect(route('post-jobs'));

    expect(Job::find($job->id))->toBeNull();
});

test('job seekers only see active unexpired postings', function () {
    $hr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();
    $visible = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $hr->company_id,
        'title' => 'Visible Laravel Role',
        'status' => 'active',
        'expires_at' => now()->addWeek(),
    ]);
    Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $hr->company_id,
        'title' => 'Draft Role',
        'status' => 'draft',
    ]);
    Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $hr->company_id,
        'title' => 'Expired Role',
        'status' => 'active',
        'expires_at' => now()->subDay(),
    ]);

    $this->actingAs(User::factory()->jobSeeker()->create())
        ->get(route('browse-jobs'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('job-seeker/BrowseJobs')
            ->has('jobs.data', 1)
            ->where('jobs.data.0.id', $visible->id)
            ->where('jobs.data.0.title', 'Visible Laravel Role')
        );
});

test('job seekers can filter browse results by search type and remote', function () {
    $hr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();
    $match = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $hr->company_id,
        'title' => 'Remote Vue Designer',
        'type' => 'contract',
        'remote' => 'remote',
        'status' => 'active',
    ]);
    Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $hr->company_id,
        'title' => 'Onsite PHP Engineer',
        'type' => 'full_time',
        'remote' => 'on_site',
        'status' => 'active',
    ]);

    $this->actingAs(User::factory()->jobSeeker()->create())
        ->get(route('browse-jobs', [
            'search' => 'Vue',
            'type' => 'contract',
            'remote' => 'remote',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobs.data', 1)
            ->where('jobs.data.0.id', $match->id)
            ->where('filters.search', 'Vue')
        );
});
