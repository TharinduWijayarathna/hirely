<?php

test('the about page renders successfully', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('public/About'));
});

test('the public welcome page is branded hirely', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Welcome'))
        ->assertSee('Hirely')
        ->assertDontSee('TalentTune');
});

test('the application name defaults to hirely', function () {
    expect(config('app.name'))->toBe('Hirely');
});

test('the dashboard is branded hirely', function () {
    $this->actingAs(\App\Models\User::factory()->jobSeeker()->create(['name' => 'Alex Rivera']))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('auth.user.name', 'Alex Rivera')
        )
        ->assertSee('Hirely')
        ->assertDontSee('TalentTune');
});
