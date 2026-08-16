<?php

use App\Models\Company;
use App\Models\CvDocument;
use App\Models\Interview;
use App\Models\InterviewTemplate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\MockInterviewSession;
use App\Models\User;

test('guests are redirected away from role-gated pages', function () {
    $this->get(route('browse-jobs'))->assertRedirect(route('login'));
    $this->get(route('post-jobs'))->assertRedirect(route('login'));
    $this->get(route('admin.payments'))->assertRedirect(route('login'));
    $this->get(route('mock-interview'))->assertRedirect(route('login'));
});

test('candidates cannot open or complete another persons recruitment interview', function () {
    $company = Company::factory()->create();
    $hr = User::factory()->hrProfessional($company->id)->create();
    $job = Job::factory()->create([
        'user_id' => $hr->id,
        'company_id' => $company->id,
    ]);
    $owner = User::factory()->jobSeeker()->create();
    $other = User::factory()->jobSeeker()->create();
    $application = JobApplication::factory()->create([
        'user_id' => $owner->id,
        'job_id' => $job->id,
    ]);
    $interview = Interview::factory()->create([
        'interview_template_id' => InterviewTemplate::factory()->create([
            'user_id' => $hr->id,
            'company_id' => $company->id,
        ])->id,
        'job_application_id' => $application->id,
        'job_id' => $job->id,
        'candidate_id' => $owner->id,
        'status' => 'in_progress',
        'questions' => [['category' => 'technical', 'text' => 'What is REST?']],
    ]);

    $this->actingAs($other)
        ->get(route('interviews.show', $interview))
        ->assertForbidden();

    $this->actingAs($other)
        ->put(route('interviews.update', $interview), [
            'answers' => ['What is REST?' => 'Hijacked'],
            'status' => 'completed',
        ])
        ->assertForbidden();
});

test('job seekers cannot open another users mock interview session', function () {
    $owner = User::factory()->jobSeeker()->create();
    $other = User::factory()->jobSeeker()->create();
    $session = MockInterviewSession::create([
        'user_id' => $owner->id,
        'type' => 'technical',
        'difficulty' => 'intermediate',
        'mode' => 'text',
        'status' => 'pending',
    ]);

    $this->actingAs($other)
        ->get(route('mock-interview.session', $session))
        ->assertForbidden();
});

test('job seekers cannot delete another users cv', function () {
    $document = CvDocument::factory()->create();
    $other = User::factory()->jobSeeker()->create();

    $this->actingAs($other)
        ->delete(route('cv-review.destroy', $document))
        ->assertForbidden();
});

test('hr cannot delete another company interview template', function () {
    $template = InterviewTemplate::factory()->create();
    $hr = User::factory()->hrProfessional(Company::factory()->create()->id)->create();

    $this->actingAs($hr)
        ->delete(route('interview-templates.destroy', $template))
        ->assertForbidden();
});
