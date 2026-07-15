<?php

namespace Tests\Feature;

use App\Exceptions\Authentication\ExternalAuthenticationException;
use App\Models\Employee;
use App\Models\User;
use App\Services\Authentication\UserAccessValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleSsoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_google_as_primary_and_password_as_fallback(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee(route('auth.google.redirect'), false);
        $response->assertSee('Masuk dengan akun Google institusi');
        $response->assertSee('atau masuk dengan email dan kata sandi');
        $response->assertDontSee('disabled', false);
    }

    public function test_redirect_route_redirects_to_google_provider(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser());

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString('socialite.fake/google', $response->headers->get('Location'));
    }

    public function test_redirect_route_rejects_when_google_configuration_is_incomplete(): void
    {
        config()->set('services.google.client_id', null);

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_registered_user_can_login_with_google_and_initial_linking_is_created(): void
    {
        $user = $this->createActiveEmployeeUser([
            'email' => 'dosen@stikesadvaitamedika.ac.id',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-123',
            'email' => 'dosen@stikesadvaitamedika.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->fresh()->google_id);
        $this->assertNotNull($user->fresh()->google_linked_at);
        $this->assertSame('google', $user->fresh()->last_login_provider);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'google_login_success',
            'module' => 'auth',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'google_account_linked',
            'module' => 'auth',
        ]);
    }

    public function test_subsequent_google_login_uses_google_id_as_primary_mapping(): void
    {
        $user = $this->createActiveEmployeeUser([
            'email' => 'pegawai@stikesadvaitamedika.ac.id',
            'role' => User::ROLE_EMPLOYEE,
            'google_id' => 'google-linked-1',
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-linked-1',
            'email' => 'alias@stikesadvaitamedika.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_registered_gmail_account_can_login(): void
    {
        $user = $this->createActiveEmployeeUser([
            'email' => 'user@gmail.com',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-456',
            'email' => 'user@gmail.com',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-456', $user->fresh()->google_id);
    }

    public function test_registered_company_email_account_can_login(): void
    {
        $user = $this->createActiveEmployeeUser([
            'email' => 'pegawai@company.com',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-company-1',
            'email' => 'pegawai@company.com',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_callback_rejects_invalid_google_email_format(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-invalid-email',
            'email' => 'invalid-email',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_callback_rejects_unknown_email(): void
    {
        Socialite::fake('google', $this->fakeGoogleUser([
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
        $this->createActiveEmployeeUser([
            'email' => 'inactive@advaita.ac.id',
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => false,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-999',
            'email' => 'inactive@advaita.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_callback_rejects_inactive_employee_status_when_employee_relation_exists(): void
    {
        $user = $this->createActiveEmployeeUser([
            'email' => 'resigned@advaita.ac.id',
            'role' => User::ROLE_EMPLOYEE,
        ], 'resigned');

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-resigned',
            'email' => 'resigned@advaita.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame('google-resigned', $user->fresh()->google_id);
    }

    public function test_callback_rejects_when_employee_role_has_no_linked_employee_record(): void
    {
        User::factory()->create([
            'email' => 'noemployee@advaita.ac.id',
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-no-employee',
            'email' => 'noemployee@advaita.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_access_validator_rejects_unknown_role(): void
    {
        $validator = app(UserAccessValidator::class);
        $user = new User([
            'email' => 'odd-role@advaita.ac.id',
            'role' => 'weird_role',
            'is_active' => true,
        ]);

        $this->expectException(ExternalAuthenticationException::class);
        $validator->validate($user);
    }

    public function test_callback_rejects_google_id_conflict(): void
    {
        $this->createActiveEmployeeUser([
            'email' => 'conflict@advaita.ac.id',
            'role' => User::ROLE_EMPLOYEE,
            'google_id' => 'existing-google-id',
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'different-google-id',
            'email' => 'conflict@advaita.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'google_account_link_rejected',
            'module' => 'auth',
        ]);
    }

    public function test_callback_rejects_unverified_google_email_when_provider_reports_that_status(): void
    {
        $this->createActiveEmployeeUser([
            'email' => 'verifyme@stikesadvaitamedika.ac.id',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-unverified',
            'email' => 'verifyme@stikesadvaitamedika.ac.id',
        ], false));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_employee_role_redirects_to_employee_dashboard(): void
    {
        $user = $this->createActiveEmployeeUser([
            'email' => 'employee@advaita.ac.id',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-employee',
            'email' => 'employee@advaita.ac.id',
        ]));

        $this->get('/auth/google/callback')->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_finance_role_redirects_to_finance_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'finance@advaita.ac.id',
            'role' => User::ROLE_FINANCE,
            'is_active' => true,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-finance',
            'email' => 'finance@advaita.ac.id',
        ]));

        $this->get('/auth/google/callback')->assertRedirect('/finance/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_hr_role_redirects_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@advaita.ac.id',
            'role' => User::ROLE_ADMIN_HR,
            'is_active' => true,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-admin',
            'email' => 'admin@advaita.ac.id',
        ]));

        $this->get('/auth/google/callback')->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_super_admin_role_redirects_to_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'super@advaita.ac.id',
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-super-admin',
            'email' => 'super@advaita.ac.id',
        ]));

        $this->get('/auth/google/callback')->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_regenerates_session_and_updates_metadata(): void
    {
        $user = $this->createActiveEmployeeUser([
            'email' => 'session@stikesadvaitamedika.ac.id',
            'role' => User::ROLE_EMPLOYEE,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-session',
            'email' => 'session@stikesadvaitamedika.ac.id',
        ]));

        $this->startSession();
        $before = app('session')->getId();

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect('/employee/dashboard');
        $this->assertNotSame($before, app('session')->getId());
        $this->assertSame('127.0.0.1', (string) $user->fresh()->last_login_ip);
    }

    public function test_existing_password_login_still_works_for_employee_account(): void
    {
        $user = $this->createActiveEmployeeUser([
            'email' => 'password.user@advaita.ac.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'PASSWORD.USER@advaita.ac.id',
            'password' => 'password',
        ]);

        $response->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('password', $user->fresh()->last_login_provider);
    }

    public function test_super_admin_can_still_login_with_password(): void
    {
        $user = User::factory()->create([
            'email' => 'super.password@advaita.ac.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'super.password@advaita.ac.id',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_existing_role_access_remains_unchanged_after_google_login(): void
    {
        $user = User::factory()->create([
            'email' => 'admin.access@advaita.ac.id',
            'role' => User::ROLE_ADMIN_HR,
            'is_active' => true,
        ]);

        Socialite::fake('google', $this->fakeGoogleUser([
            'id' => 'google-admin-access',
            'email' => 'admin.access@advaita.ac.id',
        ]));

        $this->get('/auth/google/callback')->assertRedirect('/admin/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->get('/hr/employees')->assertOk();
        $this->get('/finance/dashboard')->assertForbidden();
    }

    private function createActiveEmployeeUser(array $attributes = [], string $employmentStatus = 'active'): User
    {
        $user = User::factory()->create($attributes);

        Employee::factory()->create([
            'user_id' => $user->id,
            'employment_status' => $employmentStatus,
        ]);

        return $user;
    }

    private function fakeGoogleUser(array $attributes = [], ?bool $emailVerified = true): SocialiteUser
    {
        $user = SocialiteUser::fake(array_merge([
            'id' => 'google-default',
            'email' => 'default@stikesadvaitamedika.ac.id',
        ], $attributes));

        if ($emailVerified !== null) {
            $user->user['email_verified'] = $emailVerified;
        }

        return $user;
    }
}
