<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleSsoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_active_google_workspace_link(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee(route('auth.google.redirect'), false);
        $response->assertSee('Masuk dengan Google Workspace');
        $response->assertDontSee('disabled', false);
    }

    public function test_redirect_route_redirects_to_google_provider(): void
    {
        Socialite::fake('google', SocialiteUser::fake());

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('socialite.fake/google', $response->headers->get('Location'));
    }

    public function test_callback_logs_in_existing_active_user_with_allowed_domain(): void
    {
        $user = User::factory()->create([
            'email' => 'dosen@stikesadvaitamedika.ac.id',
            'role' => 'employee',
            'is_active' => true,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'email' => 'dosen@stikesadvaitamedika.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->fresh()->google_id);
    }

    public function test_callback_rejects_unallowed_email_domain(): void
    {
        User::factory()->create([
            'email' => 'user@gmail.com',
            'role' => 'employee',
            'is_active' => true,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-456',
            'email' => 'user@gmail.com',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_callback_rejects_unknown_email(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-789',
            'email' => 'unknown@stikesadvaitamedika.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_callback_rejects_inactive_user(): void
    {
        User::factory()->create([
            'email' => 'inactive@advaita.ac.id',
            'role' => 'employee',
            'is_active' => false,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-999',
            'email' => 'inactive@advaita.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_existing_email_password_login_still_works(): void
    {
        User::factory()->create([
            'email' => 'password.user@hris.local',
            'password' => Hash::make('password'),
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'password.user@hris.local',
            'password' => 'password',
        ]);

        $response->assertRedirect('/employee/dashboard');
        $this->assertAuthenticated();
    }

    public function test_existing_role_access_remains_unchanged_after_google_login(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@advaita.ac.id',
            'role' => 'admin_hr',
            'is_active' => true,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-admin',
            'email' => 'admin@advaita.ac.id',
        ]));

        $this->get('/auth/google/callback')->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->get('/hr/employees')->assertOk();
        $this->get('/finance/dashboard')->assertForbidden();
    }
}
