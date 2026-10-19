<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_page_is_accessible_for_guest(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_admin_can_login_and_redirected_to_dashboard(): void
    {
        User::factory()->admin()->create(['username' => 'admin', 'password' => 'password']);

        $this->post('/login', [
            'username' => 'admin',
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_collector_login_redirects_to_scan(): void
    {
        User::factory()->collector()->create(['username' => 'petugas', 'password' => 'password']);

        $this->post('/login', [
            'username' => 'petugas',
            'password' => 'password',
        ])->assertRedirect('/scan');
    }

    public function test_invalid_credentials_rejected(): void
    {
        User::factory()->admin()->create(['username' => 'admin', 'password' => 'password']);

        $this->from('/login')->post('/login', [
            'username' => 'admin',
            'password' => 'wrong-password',
        ])->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->admin()->inactive()->create([
            'username' => 'inactive_admin',
            'password' => 'password',
        ]);

        $this->from('/login')->post('/login', [
            'username' => 'inactive_admin',
            'password' => 'password',
        ])->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $this->actingAsAdmin();

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
