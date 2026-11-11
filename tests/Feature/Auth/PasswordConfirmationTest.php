<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_password_confirmation_routes_are_disabled(): void
    {
        $this->get('/confirm-password')->assertNotFound();
        $this->post('/confirm-password', ['password' => 'password'])->assertNotFound();
    }
}
