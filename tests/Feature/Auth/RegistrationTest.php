<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Staff accounts are admin-provisioned (see UsersTest), not self-service.
 * /register existed as Breeze scaffolding but never fit this app: anyone
 * finding the link could hand themself full access to the register.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_closed(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
