<?php

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
