<?php

use App\Models\Company;
use App\Models\User;

test('hr can update their own company profile', function () {
    $company = Company::factory()->create([
        'name' => 'Original Labs',
        'is_verified' => false,
    ]);
    $hr = User::factory()->hrProfessional($company->id)->create();

    $this->actingAs($hr)
        ->put(route('company-settings.update'), [
            'name' => 'Hirely Labs',
            'description' => 'We hire engineers.',
            'website' => 'https://hirely.test',
            'industry' => 'Software',
            'size' => '11-50',
            'location' => 'Colombo',
            'is_verified' => true,
        ])
        ->assertRedirect(route('company-settings'));

    $company = $company->fresh();

    expect($company->name)->toBe('Hirely Labs')
        ->and($company->website)->toBe('https://hirely.test')
        ->and($company->is_verified)->toBeFalse();
});

test('hr without a company cannot update org settings', function () {
    $hr = User::factory()->hrProfessional()->create();

    $this->actingAs($hr)
        ->put(route('company-settings.update'), [
            'name' => 'Should Not Save',
        ])
        ->assertRedirect(route('company-settings'))
        ->assertSessionHasErrors('company');
});

test('job seekers cannot open company settings', function () {
    $this->actingAs(User::factory()->jobSeeker()->create())
        ->get(route('company-settings'))
        ->assertForbidden();
});
