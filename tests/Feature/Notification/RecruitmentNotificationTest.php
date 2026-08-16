<?php

use App\Models\Company;
use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Notifications\RecruitmentNotification;
use Illuminate\Support\Facades\Notification;

test('applying to a job notifies the candidate and company hr', function () {
    Notification::fake();

    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
        'status' => 'active',
        'title' => 'Backend Engineer',
    ]);
    $seeker = User::factory()->jobSeeker()->create();

    $this->actingAs($seeker)
        ->post(route('job-applications.store'), [
            'job_id' => $job->id,
            'cover_letter' => 'I would like this role.',
        ])
        ->assertRedirect(route('browse-jobs'));

    Notification::assertSentTo($seeker, RecruitmentNotification::class, fn ($notification) => $notification->type === 'application_received');
    Notification::assertSentTo($hr, RecruitmentNotification::class, fn ($notification) => $notification->type === 'application_submitted');
});

test('application status changes notify the candidate', function () {
    Notification::fake();

    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create(['user_id' => $hr->id, 'company_id' => $company->id]);
    $seeker = User::factory()->jobSeeker()->create();
    $application = JobApplication::factory()->create([
        'user_id' => $seeker->id,
        'job_id' => $job->id,
        'status' => 'pending',
    ]);

    $this->actingAs($hr)
        ->put(route('review-candidates.update', $application), [
            'status' => 'shortlisted',
        ])
        ->assertRedirect(route('review-candidates'));

    Notification::assertSentTo($seeker, RecruitmentNotification::class, fn ($notification) => $notification->type === 'application_status');
});

test('assigning an interview notifies the candidate', function () {
    Notification::fake();

    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create(['user_id' => $hr->id, 'company_id' => $company->id]);
    $seeker = User::factory()->jobSeeker()->create();
    $application = JobApplication::factory()->create([
        'user_id' => $seeker->id,
        'job_id' => $job->id,
    ]);
    $template = InterviewTemplate::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
    ]);

    $this->actingAs($hr)
        ->post(route('review-candidates.interviews.store', $application), [
            'interview_template_id' => $template->id,
        ])
        ->assertRedirect(route('review-candidates'));

    Notification::assertSentTo($seeker, RecruitmentNotification::class, fn ($notification) => $notification->type === 'interview_assigned');
});

test('completing an interview notifies hr of review and ranking', function () {
    Notification::fake();
    config(['services.gemini.api_key' => '']);

    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create(['user_id' => $hr->id, 'company_id' => $company->id]);
    $seeker = User::factory()->jobSeeker()->create();
    $application = JobApplication::factory()->create([
        'user_id' => $seeker->id,
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
        'candidate_id' => $seeker->id,
        'status' => 'in_progress',
        'started_at' => now()->subMinutes(10),
        'questions' => [['category' => 'technical', 'text' => 'What is REST?']],
    ]);

    $this->actingAs($seeker)
        ->put(route('interviews.update', $interview), [
            'answers' => ['What is REST?' => 'Representational state transfer'],
            'status' => 'completed',
        ])
        ->assertRedirect(route('interviews.show', $interview));

    Notification::assertSentTo($hr, RecruitmentNotification::class, fn ($notification) => $notification->type === 'interview_completed');
    Notification::assertSentTo($hr, RecruitmentNotification::class, fn ($notification) => $notification->type === 'ranking_ready');
});

test('users can mark notifications as read', function () {
    $user = User::factory()->jobSeeker()->create();
    $user->notify(new RecruitmentNotification(
        'application_received',
        'Application received',
        'Your application was submitted.',
        '/job-applications',
    ));

    $notification = $user->notifications()->first();

    $this->actingAs($user)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('users cannot mark another users notification as read', function () {
    $owner = User::factory()->jobSeeker()->create();
    $other = User::factory()->jobSeeker()->create();
    $owner->notify(new RecruitmentNotification(
        'application_received',
        'Application received',
        'Your application was submitted.',
        '/job-applications',
    ));

    $notification = $owner->notifications()->first();

    $this->actingAs($other)
        ->post(route('notifications.read', $notification->id))
        ->assertNotFound();
});
