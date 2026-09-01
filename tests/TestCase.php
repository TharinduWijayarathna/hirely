<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.api_key' => '',
            'services.google.tts_api_key' => '',
            'payments.required' => true,
            'payments.webhook_enabled' => true,
        ]);
    }
}
