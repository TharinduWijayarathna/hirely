<?php

use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('organizations are listed on the public organization page', function () {
    $company = Company::factory()->create(['name' => 'Northwind']);
    Job::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'title' => 'Org Page Role',
    ]);

    $this->get(route('organization.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/Organizations')
            ->has('organizations', 1)
            ->where('organizations.0.name', 'Northwind')
        );
});

test('an organization page lists that companys live jobs', function () {
    $company = Company::factory()->create(['name' => 'Contoso', 'slug' => 'contoso']);
    $job = Job::factory()->create([
        'company_id' => $company->id,
        'status' => 'active',
        'title' => 'Backend Engineer',
    ]);
    Job::factory()->create([
        'company_id' => $company->id,
        'status' => 'draft',
        'title' => 'Secret Draft',
    ]);

    $this->get(route('organization.show', $company))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/OrganizationShow')
            ->where('organization.slug', 'contoso')
            ->has('jobs', 1)
            ->where('jobs.0.id', $job->id)
            ->where('jobs.0.title', 'Backend Engineer')
        );
});

test('hr can register by creating an organization', function () {
    Notification::fake();

    $this->get(route('organization.register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/OrganizationRegister'));

    $this->post(route('organization.register.store'), [
        'organization_name' => 'Brightline Studio',
        'organization_location' => 'Colombo',
        'industry' => 'Software',
        'name' => 'Priya HR',
        'email' => 'priya@brightline.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'admin',
    ])->assertRedirect(route('verification.notice'));

    $this->assertAuthenticated();

    $user = User::where('email', 'priya@brightline.test')->first();
    $company = Company::where('name', 'Brightline Studio')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe('hr_professional')
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->company_id)->toBe($company->id)
        ->and($company->slug)->toBe('brightline-studio')
        ->and($company->location)->toBe('Colombo');

    Notification::assertSentTo($user, VerifyEmail::class);

    $this->get(route('organization.show', $company))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('organization.name', 'Brightline Studio'));
});

test('public candidate registration cannot create an hr account', function () {
    $this->post(route('register.store'), [
        'name' => 'Seeker',
        'email' => 'seeker@hirely.test',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'hr_professional',
        'organization_name' => 'Should Not Exist',
    ]);

    $user = User::where('email', 'seeker@hirely.test')->first();

    expect($user->role)->toBe('job_seeker')
        ->and($user->company_id)->toBeNull()
        ->and(Company::where('name', 'Should Not Exist')->exists())->toBeFalse();
});
