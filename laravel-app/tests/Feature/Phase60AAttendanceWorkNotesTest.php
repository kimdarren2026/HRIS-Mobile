<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 60A — attendance check-in work plan / checkout work result.
 *
 * Concurrency (Phase 59D leave locking) and session enforcement (Phase 59C)
 * are explicitly untouched by this phase — see the separate targeted filter
 * runs in the final report rather than duplicated here.
 */
class Phase60AAttendanceWorkNotesTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;

    private Employee $employee;

    private OfficeLocation $office;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
        Storage::fake('local');

        $dept     = Department::create(['name' => 'Engineering', 'description' => '']);
        $position = Position::create(['name' => 'Dev', 'department_id' => $dept->id]);

        $this->employeeUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);

        $this->employee = Employee::create([
            'user_id'            => $this->employeeUser->id,
            'nik'                => 'NIK-60A-001',
            'department_id'      => $dept->id,
            'position_id'        => $position->id,
            'join_date'          => '2026-01-01',
            'employment_status'  => 'active',
            'phone_number'       => '+62812345678',
        ]);

        $this->office = OfficeLocation::create([
            'name'          => 'Main Office',
            'latitude'      => -6.2000000,
            'longitude'     => 106.8166660,
            'radius_meters' => 100,
            'is_active'     => true,
        ]);
    }

    private function coordsWithin(): array
    {
        return ['lat' => -6.2001000, 'lng' => 106.8166660, 'accuracy' => 5];
    }

    private function coordsOutside(): array
    {
        return ['lat' => -6.2100000, 'lng' => 106.8166660, 'accuracy' => 5];
    }

    private function validPhoto(): UploadedFile
    {
        return UploadedFile::fake()->image('selfie.jpg', 200, 200)->size(100);
    }

    private function validWorkPlan(): string
    {
        return 'Menyelesaikan laporan keuangan bulanan dan memeriksa dokumen pendukung.';
    }

    private function validWorkResult(): string
    {
        return 'Laporan selesai 80%. Data transaksi sudah diverifikasi dan menunggu satu dokumen pendukung.';
    }

    private function validReason(): string
    {
        return 'Kunjungan klien di luar kantor hari ini, disetujui oleh atasan.';
    }

    private function makeApprovedRecordToday(?string $workPlan = null): AttendanceRecord
    {
        return AttendanceRecord::create([
            'employee_id'        => $this->employee->id,
            'attendance_date'    => today(),
            'check_in_time'      => now()->subHours(4),
            'check_in_lat'       => -6.2001000,
            'check_in_lng'       => 106.8166660,
            'check_in_work_plan' => $workPlan,
            'status'             => 'APPROVED',
        ]);
    }

    private function makeHrApprover(): User
    {
        $hr   = User::factory()->create(['role' => 'admin_hr', 'is_active' => true]);
        $dept = Department::create(['name' => 'HR Dept '.uniqid(), 'description' => '']);
        $pos  = Position::create(['name' => 'HR Role '.uniqid(), 'department_id' => $dept->id]);

        Employee::factory()->create([
            'user_id'           => $hr->id,
            'department_id'     => $dept->id,
            'position_id'       => $pos->id,
            'employment_status' => 'active',
        ]);

        return $hr;
    }

    // ================================================================
    // CHECK-IN INSIDE RADIUS (1-11)
    // ================================================================

    // 1. Inside-radius check-in without work plan is rejected.
    public function test_checkin_inside_radius_without_work_plan_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), ['photo' => $this->validPhoto()]))
            ->assertSessionHasErrors('check_in_work_plan');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // 2. Work plan shorter than 10 characters is rejected.
    public function test_checkin_work_plan_shorter_than_10_chars_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => 'Rapat',
            ]))
            ->assertSessionHasErrors('check_in_work_plan');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // 3. Work plan longer than 1000 characters is rejected.
    public function test_checkin_work_plan_longer_than_1000_chars_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => str_repeat('a', 1001),
            ]))
            ->assertSessionHasErrors('check_in_work_plan');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // 4. Whitespace-only work plan is rejected.
    public function test_checkin_work_plan_whitespace_only_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => str_repeat(' ', 15),
            ]))
            ->assertSessionHasErrors('check_in_work_plan');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // 5, 7. Valid work plan creates attendance; inside-radius status stays APPROVED.
    public function test_checkin_with_valid_work_plan_creates_approved_attendance(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ]))
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('APPROVED', $record->status);
        $this->assertSame($this->validWorkPlan(), $record->check_in_work_plan);
    }

    // 6. Work plan is stored exactly as-is after trimming surrounding whitespace.
    public function test_checkin_work_plan_is_trimmed_before_storage(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => "   {$this->validWorkPlan()}   ",
            ]))
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertSame($this->validWorkPlan(), $record->check_in_work_plan);
    }

    // 8. GPS distance/accuracy are still stored exactly as before (untouched by this phase).
    public function test_checkin_gps_distance_and_accuracy_still_stored(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ]));

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($record->distance_from_office);
        $this->assertEqualsWithDelta(5.0, (float) $record->check_in_accuracy, 0.01);
    }

    // 9. Selfie flow still works — photo stored on private disk.
    public function test_checkin_selfie_flow_still_works(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ]));

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        Storage::disk('local')->assertExists($record->check_in_photo_path);
    }

    // 10. Validation failure does not create any attendance record (already
    // covered by test 1's assertDatabaseMissing, restated explicitly here).
    public function test_checkin_validation_failure_does_not_create_partial_record(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => 'short',
            ]));

        $this->assertSame(0, AttendanceRecord::where('employee_id', $this->employee->id)->count());
    }

    // 11. Validation failure creates no notification and no audit log.
    public function test_checkin_validation_failure_creates_no_notification_or_audit(): void
    {
        $this->makeHrApprover();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsOutside(), [
                'photo' => $this->validPhoto(),
                'reason' => $this->validReason(),
                'check_in_work_plan' => '   ',
            ]));

        $this->assertSame(0, Notification::count());
        $this->assertFalse(AuditLog::where('action', 'submit_attendance')->exists());
    }

    // ================================================================
    // CHECK-IN OUTSIDE RADIUS (12-20)
    // ================================================================

    // 12. Outside-radius check-in without work plan is rejected.
    public function test_checkin_outside_radius_without_work_plan_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsOutside(), [
                'photo' => $this->validPhoto(),
                'reason' => $this->validReason(),
            ]))
            ->assertSessionHasErrors('check_in_work_plan');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // 13. Outside-radius check-in without a reason is rejected.
    public function test_checkin_outside_radius_without_reason_is_rejected(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsOutside(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ]))
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseMissing('attendance_records', ['employee_id' => $this->employee->id]);
    }

    // 14, 15, 16, 17, 18. Outside-radius requires work plan AND reason as two
    // separate values, both persisted to their own distinct columns, status PENDING_REVIEW.
    public function test_checkin_outside_radius_saves_work_plan_and_reason_separately(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsOutside(), [
                'photo'  => $this->validPhoto(),
                'reason' => $this->validReason(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ]))
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertSame('PENDING_REVIEW', $record->status);
        $this->assertSame($this->validWorkPlan(), $record->check_in_work_plan);
        $this->assertSame($this->validReason(), $record->out_of_radius_reason);
        $this->assertNotSame($record->check_in_work_plan, $record->out_of_radius_reason);
    }

    // 19. HR notification for out-of-radius check-in still fires as before.
    public function test_checkin_outside_radius_still_notifies_hr(): void
    {
        $this->makeHrApprover();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsOutside(), [
                'photo'  => $this->validPhoto(),
                'reason' => $this->validReason(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ]));

        $this->assertSame(1, Notification::count());
    }

    // 20. Approval queue displays work plan and out-of-radius reason as two separate blocks.
    public function test_approval_queue_displays_work_plan_and_reason_separately(): void
    {
        $hr = $this->makeHrApprover();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsOutside(), [
                'photo'  => $this->validPhoto(),
                'reason' => $this->validReason(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ]));

        $response = $this->actingAs($hr)->get('/hr/approval-queue');

        $response->assertOk();
        $response->assertSee('Rencana Kerja');
        $response->assertSee($this->validWorkPlan());
        $response->assertSee($this->validReason());
    }

    // ================================================================
    // CHECKOUT (21-33)
    // ================================================================

    // 21. Checkout without work result is rejected.
    public function test_checkout_without_work_result_is_rejected(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->coordsWithin())
            ->assertSessionHasErrors('check_out_work_result');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNull($record->check_out_time);
    }

    // 22. Work result shorter than 10 characters is rejected.
    public function test_checkout_work_result_shorter_than_10_chars_is_rejected(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', array_merge($this->coordsWithin(), [
                'check_out_work_result' => 'Selesai',
            ]))
            ->assertSessionHasErrors('check_out_work_result');
    }

    // 23. Work result longer than 2000 characters is rejected.
    public function test_checkout_work_result_longer_than_2000_chars_is_rejected(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', array_merge($this->coordsWithin(), [
                'check_out_work_result' => str_repeat('a', 2001),
            ]))
            ->assertSessionHasErrors('check_out_work_result');
    }

    // 24. Whitespace-only work result is rejected.
    public function test_checkout_work_result_whitespace_only_is_rejected(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', array_merge($this->coordsWithin(), [
                'check_out_work_result' => str_repeat(' ', 15),
            ]))
            ->assertSessionHasErrors('check_out_work_result');
    }

    // 25, 26. Valid work result is stored exactly (trimmed).
    public function test_checkout_with_valid_work_result_is_saved(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', array_merge($this->coordsWithin(), [
                'check_out_work_result' => "  {$this->validWorkResult()}  ",
            ]))
            ->assertRedirect('/attendance/history');

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertSame($this->validWorkResult(), $record->check_out_work_result);
    }

    // 27. check_out_at (check_out_time) stays NULL until validation succeeds.
    public function test_checkout_time_stored_only_after_validation_succeeds(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->coordsWithin());

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();
        $this->assertNull($record->check_out_time);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', array_merge($this->coordsWithin(), [
                'check_out_work_result' => $this->validWorkResult(),
            ]));

        $this->assertNotNull($record->fresh()->check_out_time);
    }

    // 28. Validation failure creates no audit/notification success entries.
    public function test_checkout_validation_failure_creates_no_audit_or_notification(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', $this->coordsWithin());

        $this->assertFalse(AuditLog::where('action', 'submit_checkout')->exists());
        $this->assertSame(0, Notification::count());
    }

    // 29. Checkout never changes the approval status.
    public function test_checkout_does_not_change_approval_status(): void
    {
        $record = $this->makeApprovedRecordToday();
        $record->update(['status' => 'PENDING_REVIEW', 'out_of_radius_reason' => 'Field visit.']);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', array_merge($this->coordsWithin(), [
                'check_out_work_result' => $this->validWorkResult(),
            ]));

        $this->assertSame('PENDING_REVIEW', $record->fresh()->status);
    }

    // 30, 31. Old record with NULL check_in_work_plan can still check out, and
    // is not retroactively required to backfill the work plan.
    public function test_checkout_works_for_old_record_with_null_work_plan(): void
    {
        $record = $this->makeApprovedRecordToday(workPlan: null);
        $this->assertNull($record->check_in_work_plan);

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', array_merge($this->coordsWithin(), [
                'check_out_work_result' => $this->validWorkResult(),
            ]))
            ->assertRedirect('/attendance/history');

        $fresh = $record->fresh();
        $this->assertNotNull($fresh->check_out_time);
        $this->assertNull($fresh->check_in_work_plan);
        $this->assertSame($this->validWorkResult(), $fresh->check_out_work_result);
    }

    // 32. A second checkout attempt still follows existing "already checked
    // out" protection (now also supplying a valid work result).
    public function test_second_checkout_attempt_still_blocked(): void
    {
        $this->makeApprovedRecordToday();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', array_merge($this->coordsWithin(), [
                'check_out_work_result' => $this->validWorkResult(),
            ]))
            ->assertRedirect('/attendance/history');

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', array_merge($this->coordsWithin(), [
                'check_out_work_result' => 'Percobaan checkout kedua hari ini.',
            ]))
            ->assertSessionHasErrors('general');
    }

    // 33. An employee cannot check out another employee's attendance record
    // (checkout always resolves the record via the authenticated user's own
    // employee_id — there is no attendance_record id in the request at all).
    public function test_employee_cannot_checkout_another_employees_attendance(): void
    {
        $otherUser = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $otherDept = Department::create(['name' => 'Other Dept', 'description' => '']);
        $otherPos  = Position::create(['name' => 'Other Role', 'department_id' => $otherDept->id]);
        $otherEmployee = Employee::create([
            'user_id' => $otherUser->id, 'nik' => 'NIK-60A-OTHER', 'department_id' => $otherDept->id,
            'position_id' => $otherPos->id, 'join_date' => '2026-01-01', 'employment_status' => 'active',
            'phone_number' => '+62812999999',
        ]);
        AttendanceRecord::create([
            'employee_id' => $otherEmployee->id, 'attendance_date' => today(),
            'check_in_time' => now()->subHours(4), 'status' => 'APPROVED',
        ]);

        // employeeUser has no attendance record of their own today.
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-out', array_merge($this->coordsWithin(), [
                'check_out_work_result' => $this->validWorkResult(),
            ]))
            ->assertSessionHasErrors('general');

        $this->assertNull(AttendanceRecord::where('employee_id', $otherEmployee->id)->first()->check_out_time);
    }

    // ================================================================
    // DISPLAY (34-40)
    // ================================================================

    // 34, 35. Employee history displays both work plan and work result.
    public function test_employee_history_displays_work_plan_and_result(): void
    {
        $record = $this->makeApprovedRecordToday(workPlan: $this->validWorkPlan());
        $record->update(['check_out_time' => now(), 'check_out_work_result' => $this->validWorkResult()]);

        $response = $this->actingAs($this->employeeUser)->get('/attendance/history');

        $response->assertOk();
        $response->assertSee($this->validWorkPlan());
        $response->assertSee($this->validWorkResult());
    }

    // 36, 37. HR queue displays work plan, separated from the out-of-radius reason.
    public function test_hr_queue_displays_work_plan_separately_from_reason(): void
    {
        $hr = $this->makeHrApprover();
        AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'attendance_date' => today(),
            'check_in_time' => now(), 'status' => 'PENDING_REVIEW',
            'check_in_work_plan' => $this->validWorkPlan(),
            'out_of_radius_reason' => $this->validReason(),
        ]);

        $response = $this->actingAs($hr)->get('/hr/approval-queue');

        $response->assertOk();
        $response->assertSee('Rencana Kerja');
        $response->assertSee('Alasan Luar Radius');
    }

    // 38, 40. HTML/script in plan or result is escaped, never rendered raw.
    public function test_html_in_work_plan_and_result_is_escaped(): void
    {
        $malicious = '<script>alert(1)</script> laporan berbahaya panjang cukup';
        $record = $this->makeApprovedRecordToday(workPlan: $malicious);
        $record->update(['check_out_time' => now(), 'check_out_work_result' => $malicious]);

        $html = $this->actingAs($this->employeeUser)->get('/attendance/history')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    // 39. Old records with NULL work plan/result still render without error.
    public function test_old_record_with_null_work_notes_renders_without_error(): void
    {
        AttendanceRecord::create([
            'employee_id' => $this->employee->id, 'attendance_date' => today()->subDay(),
            'check_in_time' => now()->subDay(), 'status' => 'APPROVED',
            // check_in_work_plan / check_out_work_result intentionally omitted (legacy row).
        ]);

        $this->actingAs($this->employeeUser)
            ->get('/attendance/history')
            ->assertOk();
    }

    // ================================================================
    // REGRESSION (41-50)
    // ================================================================

    // 41. Phase 58G inside/outside classification is unchanged.
    public function test_inside_outside_classification_unchanged(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ]));
        $this->assertSame('APPROVED', AttendanceRecord::where('employee_id', $this->employee->id)->first()->status);
    }

    // 42. GPS accuracy remains a non-blocker (large accuracy still succeeds).
    public function test_accuracy_is_not_a_blocker(): void
    {
        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', [
                'lat' => -6.2001000, 'lng' => 106.8166660, 'accuracy' => 999,
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ])
            ->assertRedirect('/attendance/history');

        $this->assertSame('APPROVED', AttendanceRecord::where('employee_id', $this->employee->id)->first()->status);
    }

    // 43. Outside-radius HR approval still works end-to-end.
    public function test_outside_radius_approval_still_works(): void
    {
        $hr = $this->makeHrApprover();

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsOutside(), [
                'photo' => $this->validPhoto(),
                'reason' => $this->validReason(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ]));

        $record = AttendanceRecord::where('employee_id', $this->employee->id)->first();

        $this->actingAs($hr)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Verified.'])
            ->assertRedirect();

        $this->assertSame('APPROVED', $record->fresh()->status);
    }

    // 44. Attendance self-approval protection (pre-Phase-60A) is untouched.
    public function test_attendance_self_approval_protection_still_works(): void
    {
        $hr = $this->makeHrApprover();
        $hrEmployee = $hr->employee;
        $record = AttendanceRecord::create([
            'employee_id' => $hrEmployee->id, 'attendance_date' => today(),
            'check_in_time' => now(), 'status' => 'PENDING_REVIEW',
            'out_of_radius_reason' => 'Self attempt.',
        ]);

        $this->actingAs($hr)
            ->post("/hr/attendance/{$record->id}/approve", ['approval_note' => 'Self approve attempt'])
            ->assertForbidden();
    }

    // 45. Duplicate attendance protection is untouched.
    public function test_duplicate_attendance_protection_still_works(): void
    {
        $this->makeApprovedRecordToday(workPlan: $this->validWorkPlan());

        $this->actingAs($this->employeeUser)
            ->post('/attendance/check-in', array_merge($this->coordsWithin(), [
                'photo' => $this->validPhoto(),
                'check_in_work_plan' => $this->validWorkPlan(),
            ]))
            ->assertSessionHasErrors('general');

        $this->assertSame(1, AttendanceRecord::where('employee_id', $this->employee->id)->count());
    }

    // 46-50: Leave maker-checker (Phase 59B), active session enforcement
    // (Phase 59C), leave concurrency hardening (Phase 59D), the leave module,
    // and the payroll module are all untouched by this phase's diff (verified
    // via `git diff --stat` in the final report) and are regression-tested by
    // running their own existing suites directly — see the targeted test
    // commands in the Phase 60A final report rather than duplicating them here.
}
