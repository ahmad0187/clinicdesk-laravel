<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/patients')->assertRedirect('/login');
    }

    public function test_staff_can_sign_in_and_out(): void
    {
        $user = User::factory()->create(['email' => 'leo@example.com']);

        $this->post('/login', ['email' => 'leo@example.com', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_and_disabled_accounts_are_rejected(): void
    {
        User::factory()->create(['email' => 'leo@example.com']);
        User::factory()->create(['email' => 'gone@example.com', 'active' => false]);

        $this->from('/login')->post('/login', ['email' => 'leo@example.com', 'password' => 'nope'])->assertSessionHasErrors('email');
        $this->from('/login')->post('/login', ['email' => 'gone@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->freezeTime();
        User::factory()->create(['email' => 'leo@example.com']);
        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => 'leo@example.com', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'leo@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 60 seconds.']);
        $this->assertGuest();
    }
}
