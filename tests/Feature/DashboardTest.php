<?php

use App\Models\Company;
use App\Models\CvDocument;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Payment;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('job seeker dashboard uses live cv and application counts', function () {
    $user = User::factory()->jobSeeker()->create();
    CvDocument::factory()->create(['user_id' => $user->id]);
    JobApplication::factory()->create(['user_id' => $user->id, 'status' => 'pending']);
    Payment::factory()->create([
        'user_id' => $user->id,
        'amount' => 19.99,
        'status' => 'succeeded',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('stats.cv_reviews', 1)
            ->where('stats.applications', 1)
            ->where('stats.interviews_completed', 0)
            ->where('charges.count', 1)
            ->where('charges.total', fn ($total) => round((float) $total, 2) === 19.99)
            ->has('charts.charges', 6)
            ->has('charts.applications', 6)
            ->has('breakdown')
        );
});

test('hr dashboard counts only the company pipeline', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
        'status' => 'active',
    ]);
    JobApplication::factory()->count(2)->create(['job_id' => $job->id, 'status' => 'pending']);
    JobApplication::factory()->create(['job_id' => $job->id, 'status' => 'reviewing']);
    Payment::factory()->create([
        'user_id' => $hr->id,
        'amount' => 49,
        'status' => 'succeeded',
    ]);

    $other = Company::factory()->create();
    $otherJob = Job::factory()->create([
        'user_id' => User::factory()->hrProfessional($other->id),
        'company_id' => $other->id,
    ]);
    JobApplication::factory()->create(['job_id' => $otherJob->id]);

    $this->actingAs($hr)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('stats.active_jobs', 1)
            ->where('stats.total_applicants', 3)
            ->where('stats.under_review', 3)
            ->where('funnel.0.status', 'pending')
            ->where('funnel.0.count', 2)
            ->where('charges.total', fn ($total) => round((float) $total, 2) === 49.0)
            ->has('charts.applications', 6)
        );
});

test('admin dashboard reports live user company and charge counts', function () {
    $admin = User::factory()->admin()->create();
    Company::factory()->create();
    Payment::factory()->create([
        'amount' => 99,
        'status' => 'succeeded',
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('stats.total_users', fn ($count) => $count >= 1)
            ->where('stats.companies', fn ($count) => $count >= 1)
            ->where('charges.total', fn ($total) => $total >= 99)
            ->has('charts.users', 6)
            ->has('charts.charges', 6)
            ->has('breakdown')
        );
});
