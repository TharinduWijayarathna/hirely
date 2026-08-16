<?php

use App\Models\User;

test('job seekers cannot access hr or admin routes', function () {
    $user = User::factory()->jobSeeker()->create();

    $this->actingAs($user)
        ->get(route('post-jobs'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.payments'))
        ->assertForbidden();
});

test('hr professionals cannot access job seeker or admin routes', function () {
    $user = User::factory()->hrProfessional()->create();

    $this->actingAs($user)
        ->get(route('mock-interview'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('browse-jobs'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('admin.payments'))
        ->assertForbidden();
});

test('admins cannot access job seeker or hr routes', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('browse-jobs'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('post-jobs'))
        ->assertForbidden();
});

test('each role can access its own dashboard and primary pages', function () {
    $seeker = User::factory()->jobSeeker()->create();
    $hr = User::factory()->hrProfessional()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($seeker)->get(route('dashboard'))->assertOk();
    $this->actingAs($seeker)->get(route('browse-jobs'))->assertOk();
    $this->actingAs($seeker)->get(route('payments'))->assertOk();

    $this->actingAs($hr)->get(route('post-jobs'))->assertOk();
    $this->actingAs($hr)->get(route('subscriptions'))->assertOk();
    $this->actingAs($hr)->get(route('interview-templates'))->assertOk();

    $this->actingAs($admin)->get(route('company-management'))->assertOk();
    $this->actingAs($admin)->get(route('admin.payments'))->assertOk();
});
