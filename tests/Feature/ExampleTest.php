<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_guests_to_staff_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('staff.login'));
    }
}
