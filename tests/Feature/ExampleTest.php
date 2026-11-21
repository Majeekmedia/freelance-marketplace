<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The landing page renders.
 *
 * `RefreshDatabase` is required, not optional: the home page queries published
 * jobs and ledger totals, so without migrations the request fails on a missing
 * table rather than testing anything. The default Laravel scaffold leaves this
 * trait commented out, which is fine for a static page and wrong for this one.
 */
class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
