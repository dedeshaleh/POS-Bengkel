<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root path redirects to login (auth middleware).
     */
    public function test_the_application_returns_a_redirect_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
