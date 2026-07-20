<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase57LocalizationPayslipTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $adminHr;
    private User $financeUser;
    private User $employeeUser;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('local');

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->superAdmin   = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->adminHr      = User::factory()->create(['role' => 'admin_hr',    'is_active' => true]);
        $this->financeUser  = User::factory()->create(['role' => 'finance',     'is_active' => true]);
        $this->employeeUser = User::factory()->create(['role' => 'employee',    'is_active' => true]);

        $this->employee = Employee::create([
            'user_id'             => $this->employeeUser->id,
            'nik'                 => 'P57-EMP-001',
            'department_id'       => $dept->id,
            'position_id'         => $position->id,
            'join_date'           => '2026-01-01',
            'employment_status'   => 'active',
            'phone_number'        => '+62812345678',
            'address'             => 'Test Address',
            'bank_name'           => 'Test Bank',
            'bank_account_number' => '1234567890',
        ]);
    }

    // ── 1 & 2: Halaman utama & navigasi utama berbahasa Indonesia ─────────────

    public function test_admin_dashboard_uses_indonesian_labels(): void
    {
        $this->actingAs($this->adminHr)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Hai,')
            ->assertSee('AKSI CEPAT')
            ->assertDontSee('QUICK ACTIONS');
    }

    public function test_admin_dashboard_bottom_nav_uses_indonesian_labels(): void
    {
        $this->actingAs($this->adminHr)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Beranda')
            ->assertSee('Pegawai')
            ->assertSee('Persetujuan')
            ->assertSee('Laporan');
    }

    public function test_hr_approval_queue_uses_indonesian_labels(): void
    {
        $this->actingAs($this->adminHr)
            ->get('/hr/approval-queue')
            ->assertOk()
            ->assertSee('Antrean Persetujuan HR')
            ->assertSee('Kehadiran')
            ->assertSee('Cuti');
    }

    // ── 3: Halaman Settings berbahasa Indonesia ────────────────────────────────

    public function test_settings_page_uses_indonesian_labels(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/settings')
            ->assertOk()
            ->assertSee('Pengaturan Sistem')
            ->assertSee('Lokasi Kantor')
            ->assertDontSee('System Settings')
            ->assertDontSee('Office Location & Radius');
    }

    // ── 4: Flash message office location berbahasa Indonesia ──────────────────

    public function test_office_location_create_flash_message_is_indonesian(): void
    {
        $this->actingAs($this->superAdmin)
            ->post('/settings/locations', [
                'name'          => 'Kantor Pusat',
                'latitude'      => -6.2,
                'longitude'     => 106.8166,
                'radius_meters' => 100,
                'is_active'     => '1',
            ])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success', 'Lokasi kantor berhasil dibuat.');
    }

    public function test_office_location_update_flash_message_is_indonesian(): void
    {
        $office = OfficeLocation::create([
            'name' => 'Kantor Lama', 'latitude' => -6.2, 'longitude' => 106.8166,
            'radius_meters' => 100, 'is_active' => true,
        ]);

        $this->actingAs($this->superAdmin)
            ->put("/settings/locations/{$office->id}", [
                'name' => 'Kantor Baru', 'latitude' => -6.2, 'longitude' => 106.8166,
                'radius_meters' => 150, 'is_active' => '1',
            ])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHas('success', 'Lokasi kantor berhasil diperbarui.');
    }

    // ── 5: Pesan check-in tanpa lokasi aktif berbahasa Indonesia ───────────────

    public function test_checkin_blocked_message_is_indonesian(): void
    {
        $photo = UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => -6.2, 'lng' => 106.8166, 'photo' => $photo,
            ])
            ->assertSessionHasErrors('general');

        $this->assertStringContainsString(
            'Lokasi kantor belum dikonfigurasi',
            session('errors')->first('general')
        );
    }

    // ── 6, 7, 8, 9: Route Payslip → halaman maintenance ────────────────────────

    public function test_employee_can_open_payslip_maintenance_page(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/payslip/detail')
            ->assertOk();
    }

    public function test_unauthorized_roles_get_403_on_payslip_route(): void
    {
        foreach ([$this->adminHr, $this->financeUser, $this->superAdmin] as $user) {
            $this->actingAs($user)
                ->get('/payslip/detail')
                ->assertForbidden();
        }
    }

    public function test_payslip_route_never_returns_404_or_500(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/payslip/detail');

        $this->assertNotSame(404, $response->getStatusCode());
        $this->assertNotSame(500, $response->getStatusCode());
        $response->assertOk();
    }

    public function test_payslip_maintenance_page_shows_required_copy(): void
    {
        $this->actingAs($this->employeeUser)
            ->get('/payslip/detail')
            ->assertOk()
            ->assertSee('Fitur Slip Gaji Sedang Disiapkan')
            ->assertSee('404')
            ->assertSee('Data penggajian Anda tetap aman')
            ->assertSee('Kembali ke Beranda')
            ->assertSee('Segera Hadir');
    }

    // ── 10: Halaman /reports tidak lagi menampilkan angka mockup ──────────────

    public function test_reports_page_no_longer_shows_mockup_numbers(): void
    {
        $this->actingAs($this->adminHr)
            ->get('/reports')
            ->assertOk()
            ->assertSee('Modul laporan sedang dikembangkan')
            ->assertDontSee('$685.4K')
            ->assertDontSee('94.2%')
            ->assertDontSee('IT Department')
            ->assertDontSee('Alex Rivers');
    }

    // ── 11: Tidak ada data/kode Payslip yang dihapus ───────────────────────────

    public function test_payslip_model_and_migration_are_not_deleted(): void
    {
        $this->assertFileExists(app_path('Models/Payslip.php'));

        $migrations = glob(database_path('migrations/*_create_payslips_table.php'));
        $this->assertNotEmpty($migrations, 'payslips table migration must still exist.');
    }
}
