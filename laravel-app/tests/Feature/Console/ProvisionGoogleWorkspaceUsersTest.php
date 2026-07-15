<?php

namespace Tests\Feature\Console;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class ProvisionGoogleWorkspaceUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('google_workspace_provisioning.allowed_domains', ['stikesadvaitamedika.ac.id']);
    }

    public function test_dry_run_does_not_write_to_database(): void
    {
        $this->artisan('hris:provision-google-users', [
            '--dry-run' => true,
            '--emails' => 'ayusavitri@stikesadvaitamedika.ac.id',
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['email' => 'ayusavitri@stikesadvaitamedika.ac.id']);
    }

    public function test_apply_creates_missing_personal_user(): void
    {
        $this->artisan('hris:provision-google-users', [
            '--apply' => true,
            '--emails' => 'ayusavitri@stikesadvaitamedika.ac.id',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'ayusavitri@stikesadvaitamedika.ac.id',
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => false,
        ]);
    }

    public function test_apply_does_not_duplicate_existing_user(): void
    {
        $existing = User::factory()->create([
            'email' => 'ayusavitri@stikesadvaitamedika.ac.id',
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);

        $this->artisan('hris:provision-google-users', [
            '--apply' => true,
            '--emails' => 'AyuSavitri@stikesadvaitamedika.ac.id',
        ])->assertExitCode(0);

        $this->assertSame(1, User::where('email', 'ayusavitri@stikesadvaitamedika.ac.id')->count());
        $this->assertTrue($existing->fresh()->is_active);
    }

    public function test_shared_unit_account_is_flagged_and_skipped(): void
    {
        $this->artisan('hris:provision-google-users', [
            '--apply' => true,
            '--emails' => 'webmaster@stikesadvaitamedika.ac.id',
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['email' => 'webmaster@stikesadvaitamedika.ac.id']);
    }

    public function test_invalid_domain_is_rejected(): void
    {
        $this->artisan('hris:provision-google-users', [
            '--apply' => true,
            '--emails' => 'someone@gmail.com',
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['email' => 'someone@gmail.com']);
    }

    public function test_invalid_email_format_is_rejected(): void
    {
        $this->artisan('hris:provision-google-users', [
            '--apply' => true,
            '--emails' => 'not-an-email',
        ])->assertExitCode(0);

        $this->assertSame(0, User::count());
    }

    public function test_generated_password_is_hashed_and_never_printed(): void
    {
        $exitCode = Artisan::call('hris:provision-google-users', [
            '--apply' => true,
            '--emails' => 'ayusavitri@stikesadvaitamedika.ac.id',
        ]);

        $printed = Artisan::output();

        $user = User::where('email', 'ayusavitri@stikesadvaitamedika.ac.id')->firstOrFail();

        $this->assertTrue(Hash::isHashed($user->password));
        $this->assertStringNotContainsString($user->password, $printed);
        $this->assertSame(0, $exitCode);
    }

    public function test_existing_active_user_remains_active(): void
    {
        $user = User::factory()->create([
            'email' => 'ayusavitri@stikesadvaitamedika.ac.id',
            'role' => User::ROLE_EMPLOYEE,
            'is_active' => true,
        ]);

        Employee::factory()->create([
            'user_id' => $user->id,
            'employment_status' => 'active',
        ]);

        $this->artisan('hris:provision-google-users', [
            '--apply' => true,
            '--emails' => 'ayusavitri@stikesadvaitamedika.ac.id',
        ])->assertExitCode(0);

        $fresh = $user->fresh();
        $this->assertTrue($fresh->is_active);
        $this->assertSame(User::ROLE_EMPLOYEE, $fresh->role);
    }

    public function test_provisioned_user_cannot_login_via_sso_until_activated_and_employee_linked(): void
    {
        $this->artisan('hris:provision-google-users', [
            '--apply' => true,
            '--emails' => 'ayusavitri@stikesadvaitamedika.ac.id',
        ])->assertExitCode(0);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-ayusavitri',
            'email' => 'ayusavitri@stikesadvaitamedika.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $user = User::where('email', 'ayusavitri@stikesadvaitamedika.ac.id')->firstOrFail();
        $user->forceFill(['is_active' => true])->save();
        Employee::factory()->create(['user_id' => $user->id, 'employment_status' => 'active']);

        $response = $this->get('/auth/google/callback');
        $response->assertRedirect('/employee/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_unprovisioned_email_still_rejected_by_sso(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-unprovisioned',
            'email' => 'never-provisioned@stikesadvaitamedika.ac.id',
        ]));

        $response = $this->get('/auth/google/callback');
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
