<?php

use App\Models\Company;
use App\Models\CvDocument;
use App\Models\Job;
use App\Models\MockInterviewSession;
use App\Models\User;
use Illuminate\Http\UploadedFile;

test('free hr can post five jobs and is blocked on the sixth', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    Job::factory()->count(5)->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
    ]);

    $this->actingAs($hr)
        ->post(route('post-jobs.store'), jobListingPayload())
        ->assertRedirect(route('post-jobs'))
        ->assertSessionHasErrors('plan');

    expect(Job::visibleTo($hr)->count())->toBe(5);
});

test('professional hr can post more than five jobs', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    subscribeToPlan($hr, ['jobs' => null, 'reports' => true]);
    Job::factory()->count(5)->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
    ]);

    $this->actingAs($hr)
        ->post(route('post-jobs.store'), jobListingPayload())
        ->assertRedirect(route('post-jobs'))
        ->assertSessionDoesntHaveErrors();

    expect(Job::visibleTo($hr)->count())->toBe(6);
});

test('deleting a job frees a basic-plan listing slot', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $jobs = Job::factory()->count(5)->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
    ]);

    $this->actingAs($hr)
        ->delete(route('post-jobs.destroy', $jobs->first()))
        ->assertRedirect(route('post-jobs'));

    $this->actingAs($hr)
        ->post(route('post-jobs.store'), jobListingPayload())
        ->assertRedirect(route('post-jobs'))
        ->assertSessionDoesntHaveErrors();
});

test('free hr is redirected away from recruitment reports', function () {
    $hr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();

    $this->actingAs($hr)
        ->get(route('reports'))
        ->assertRedirect(route('subscriptions'))
        ->assertSessionHasErrors('plan');
});

test('free hr is redirected away from report csv export', function () {
    $hr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();

    $this->actingAs($hr)
        ->get(route('reports.export'))
        ->assertRedirect(route('subscriptions'))
        ->assertSessionHasErrors('plan');
});

test('free seekers are limited to three mock interviews per month', function () {
    $seeker = User::factory()->jobSeeker()->create();

    foreach (range(1, 3) as $i) {
        MockInterviewSession::create([
            'user_id' => $seeker->id,
            'type' => 'technical',
            'difficulty' => 'intermediate',
            'mode' => 'text',
            'status' => 'completed',
        ]);
    }

    $this->actingAs($seeker)
        ->post(route('mock-interview.store'), [
            'type' => 'technical',
            'difficulty' => 'intermediate',
            'mode' => 'text',
        ])
        ->assertRedirect(route('mock-interview'))
        ->assertSessionHasErrors('plan');
});

test('premium seekers can start mock interviews after the free monthly cap', function () {
    $seeker = User::factory()->jobSeeker()->create();
    subscribeToPlan($seeker, [
        'mock_interviews_per_month' => null,
        'cv_documents' => null,
        'ats' => true,
    ], [
        'name' => 'professional',
        'display_name' => 'Premium Plan',
        'amount' => 19.99,
        'target_role' => 'job_seeker',
    ]);

    foreach (range(1, 3) as $i) {
        MockInterviewSession::create([
            'user_id' => $seeker->id,
            'type' => 'technical',
            'difficulty' => 'intermediate',
            'mode' => 'text',
            'status' => 'completed',
        ]);
    }

    $this->actingAs($seeker)
        ->post(route('mock-interview.store'), [
            'type' => 'technical',
            'difficulty' => 'intermediate',
            'mode' => 'text',
        ])
        ->assertRedirect();

    expect(MockInterviewSession::where('user_id', $seeker->id)->count())->toBe(4);
});

test('free seekers cannot store a second cv', function () {
    $seeker = User::factory()->jobSeeker()->create();
    CvDocument::factory()->create(['user_id' => $seeker->id]);

    $this->actingAs($seeker)
        ->post(route('cv-review.store'), [
            'cv' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('cv-review'))
        ->assertSessionHasErrors('plan');
});

test('free seekers cannot run ats scoring', function () {
    $seeker = User::factory()->jobSeeker()->create();
    CvDocument::factory()->create(['user_id' => $seeker->id]);

    $this->actingAs($seeker)
        ->post(route('ats-scoring.store'), [
            'job_description' => str_repeat('Looking for a Laravel engineer. ', 5),
        ])
        ->assertRedirect(route('ats-scoring'))
        ->assertSessionHasErrors('plan');
});
