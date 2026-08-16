<?php

use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;

test('job seekers can apply once and withdraw their own application', function () {
    $hr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();
    $job = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $hr->company_id,
        'status' => 'active',
    ]);
    $seeker = User::factory()->jobSeeker()->create();

    $this->actingAs($seeker)
        ->post(route('job-applications.store'), [
            'job_id' => $job->id,
            'cover_letter' => 'I would like this role.',
        ])
        ->assertRedirect(route('browse-jobs'));

    $application = JobApplication::where('user_id', $seeker->id)->where('job_id', $job->id)->first();

    expect($application)->not->toBeNull()
        ->and($application->status)->toBe('pending')
        ->and($application->cover_letter)->toContain('I would like this role');

    $this->actingAs($seeker)
        ->post(route('job-applications.store'), [
            'job_id' => $job->id,
        ])
        ->assertSessionHasErrors('job_id');

    $this->actingAs($seeker)
        ->delete(route('job-applications.destroy', $application))
        ->assertRedirect(route('job-applications'));

    expect(JobApplication::find($application->id))->toBeNull();
});

test('job seekers cannot withdraw someone elses application', function () {
    $application = JobApplication::factory()->create();
    $other = User::factory()->jobSeeker()->create();

    $this->actingAs($other)
        ->delete(route('job-applications.destroy', $application))
        ->assertForbidden();
});

test('hr can update application status for their company jobs', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
    ]);
    $application = JobApplication::factory()->create([
        'job_id' => $job->id,
        'status' => 'pending',
    ]);

    $this->actingAs($hr)
        ->put(route('review-candidates.update', $application), [
            'status' => 'shortlisted',
            'notes' => 'Strong Laravel background.',
        ])
        ->assertRedirect(route('review-candidates'));

    expect($application->fresh()->status)->toBe('shortlisted')
        ->and($application->fresh()->notes)->toContain('Strong Laravel');
});

test('hr cannot update applications for another company', function () {
    $application = JobApplication::factory()->create();
    $hr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();

    $this->actingAs($hr)
        ->put(route('review-candidates.update', $application), [
            'status' => 'rejected',
        ])
        ->assertForbidden();
});

test('job seekers cannot review candidate applications', function () {
    $this->actingAs(User::factory()->jobSeeker()->create())
        ->get(route('review-candidates'))
        ->assertForbidden();
});
