<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // tests don't need compiled front-end assets
    }

    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function receptionist(): User
    {
        return User::factory()->receptionist()->create();
    }

    protected function doctor(): User
    {
        return User::factory()->doctor()->create();
    }
}
