<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCheckInRequest;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function showCheckIn(): View
    {
        $user     = auth()->user();
        $employee = $user->employee;
        $office   = $this->attendanceService->getActiveOffice();

        $alreadyCheckedIn = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->exists()
            : false;

        return view('pages.attendance.checkin', [
            'alreadyCheckedIn' => $alreadyCheckedIn,
            'officeLocation'   => $office,
        ]);
    }

    public function checkIn(AttendanceCheckInRequest $request): RedirectResponse
    {
        $user     = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            return back()->withErrors(['general' => 'Data karyawan tidak ditemukan untuk akun ini.']);
        }

        // Prevent duplicate check-in (unique DB constraint is also a guard)
        if (AttendanceRecord::where('employee_id', $employee->id)->whereDate('attendance_date', today())->exists()) {
            return back()->withErrors(['general' => 'Anda sudah melakukan check-in hari ini.']);
        }

        $lat    = (float) $request->lat;
        $lng    = (float) $request->lng;
        $office = $this->attendanceService->getActiveOffice();

        // Server-side radius decision — never trust client-supplied status
        $withinRadius = $office && $this->attendanceService->isWithinRadius($lat, $lng, $office);

        if (! $withinRadius) {
            $request->validate([
                'reason' => ['required', 'string', 'min:10', 'max:500'],
            ], [
                'reason.required' => 'Alasan wajib diisi saat check-in di luar radius kantor.',
                'reason.min'      => 'Alasan minimal 10 karakter.',
                'reason.max'      => 'Alasan maksimal 500 karakter.',
            ]);
        }

        // Store selfie in private local disk (never public)
        $ext       = strtolower($request->file('photo')->getClientOriginalExtension() ?: 'jpg');
        $photoPath = sprintf(
            'attendance/%d/%s/%s.%s',
            $employee->id,
            now()->format('Y/m'),
            Str::uuid(),
            $ext
        );
        Storage::disk('local')->put($photoPath, file_get_contents($request->file('photo')->getRealPath()));

        $status = $withinRadius ? 'APPROVED' : 'PENDING_REVIEW';

        AttendanceRecord::create([
            'employee_id'          => $employee->id,
            'attendance_date'      => today(),
            'check_in_time'        => now(),
            'check_in_lat'         => $lat,
            'check_in_lng'         => $lng,
            'check_in_photo_path'  => $photoPath,
            'status'               => $status,
            'out_of_radius_reason' => $withinRadius ? null : $request->reason,
        ]);

        AuditLogService::log(
            $user,
            'submit_attendance',
            'attendance',
            "Employee #{$employee->id} check-in. Status: {$status}. Coords: {$lat},{$lng}."
        );

        $message = $withinRadius
            ? 'Check-in berhasil. Presensi Anda telah disetujui otomatis.'
            : 'Check-in terkirim. Presensi Anda menunggu review HR.';

        return redirect('/attendance/history')->with('success', $message);
    }

    public function history(): View
    {
        $employee = auth()->user()->employee;

        $records = $employee
            ? $employee->attendanceRecords()
                ->orderByDesc('attendance_date')
                ->paginate(20)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);

        return view('pages.attendance.history', compact('records'));
    }

    public function photo(AttendanceRecord $attendanceRecord): mixed
    {
        $user     = auth()->user();
        $isOwner  = $user->employee?->id === $attendanceRecord->employee_id;
        $isHrAdmin = in_array($user->role, ['admin_hr', 'super_admin'], true);

        abort_unless($isOwner || $isHrAdmin, 403);

        $path = $attendanceRecord->check_in_photo_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
