<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceCheckInRequest;
use App\Http\Requests\AttendanceCheckOutRequest;
use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
    ) {}

    public function showCheckIn(): View
    {
        $user     = auth()->user();
        $employee = $user->employee;
        $office   = $this->attendanceService->getActiveOffice();

        $todayRecord = $employee
            ? AttendanceRecord::where('employee_id', $employee->id)
                ->whereDate('attendance_date', today())
                ->first()
            : null;

        return view('pages.attendance.checkin', [
            'alreadyCheckedIn'  => $todayRecord !== null,
            'alreadyCheckedOut' => $todayRecord?->check_out_time !== null,
            'todayRecord'       => $todayRecord,
            'officeLocation'    => $office,
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

        $office = $this->attendanceService->getActiveOffice();

        if (! $office) {
            return back()->withErrors([
                'general' => 'Lokasi kantor belum dikonfigurasi. Check-in belum bisa dilakukan. Hubungi HR/Admin untuk mengatur lokasi kantor terlebih dahulu.',
            ]);
        }

        $lat      = (float) $request->lat;
        $lng      = (float) $request->lng;
        $accuracy = (float) $request->accuracy;

        // Server-side radius decision — never trust client-supplied status.
        // Only INSIDE_RADIUS may proceed past this point: OUTSIDE_RADIUS and
        // LOCATION_UNCERTAIN are rejected before the photo is ever touched, so
        // nothing is written to disk and no AttendanceRecord/notification is
        // created for a rejected location. There is no more out-of-radius
        // submission path — Phase 58F removed HR review for new attendance.
        $distance       = round($this->attendanceService->calculateDistance($lat, $lng, $office), 2);
        $classification = $this->attendanceService->classifyLocation($distance, $accuracy, $office);

        if ($classification === AttendanceService::LOCATION_UNCERTAIN) {
            return back()->withErrors([
                'general' => "Lokasi belum cukup akurat. Aktifkan Lokasi Akurat dan coba kembali. (Akurasi GPS saat ini: {$accuracy} meter)",
            ]);
        }

        if ($classification === AttendanceService::OUTSIDE_RADIUS) {
            return back()->withErrors([
                'general' => 'Anda berada di luar radius kantor. Absensi tidak dapat dilakukan.',
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

        AttendanceRecord::create([
            'employee_id'          => $employee->id,
            'attendance_date'      => today(),
            'check_in_time'        => now(),
            'check_in_lat'         => $lat,
            'check_in_lng'         => $lng,
            'distance_from_office' => $distance,
            'check_in_accuracy'    => $accuracy,
            'check_in_photo_path'  => $photoPath,
            'status'               => 'APPROVED',
        ]);

        AuditLogService::log(
            $user,
            'submit_attendance',
            'attendance',
            "Employee #{$employee->id} check-in. Status: APPROVED. Coords: {$lat},{$lng}."
        );

        return redirect('/attendance/history')->with('success', 'Check-in berhasil. Presensi Anda telah disetujui otomatis.');
    }

    public function checkOut(AttendanceCheckOutRequest $request): RedirectResponse
    {
        $user     = auth()->user();
        $employee = $user->employee;

        if (! $employee) {
            return back()->withErrors(['general' => 'Data karyawan tidak ditemukan untuk akun ini.']);
        }

        $record = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->first();

        if (! $record) {
            return back()->withErrors(['general' => 'Anda belum melakukan check-in hari ini.']);
        }

        if ($record->check_out_time !== null) {
            return back()->withErrors(['general' => 'Anda sudah melakukan check-out hari ini.']);
        }

        $lat      = (float) $request->lat;
        $lng      = (float) $request->lng;
        $accuracy = (float) $request->accuracy;

        // Same radius rule as check-in: an office may have been configured after
        // check-in, so recompute against whatever is active now. No office simply
        // means there is nothing to classify against — checkout proceeds as before.
        // Only INSIDE_RADIUS may check out; the record is left untouched on rejection.
        $office = $this->attendanceService->getActiveOffice();

        if ($office) {
            $distance       = $this->attendanceService->calculateDistance($lat, $lng, $office);
            $classification = $this->attendanceService->classifyLocation($distance, $accuracy, $office);

            if ($classification === AttendanceService::LOCATION_UNCERTAIN) {
                return back()->withErrors([
                    'general' => "Lokasi belum cukup akurat. Aktifkan Lokasi Akurat dan coba kembali. (Akurasi GPS saat ini: {$accuracy} meter)",
                ]);
            }

            if ($classification === AttendanceService::OUTSIDE_RADIUS) {
                return back()->withErrors([
                    'general' => 'Anda berada di luar radius kantor. Absensi tidak dapat dilakukan.',
                ]);
            }
        }

        // Status is intentionally NOT changed on checkout — PENDING_REVIEW records
        // remain under HR review regardless of when the employee checks out.
        $record->update([
            'check_out_time'     => now(),
            'check_out_lat'      => $lat,
            'check_out_lng'      => $lng,
            'check_out_accuracy' => $accuracy,
        ]);

        AuditLogService::log(
            $user,
            'submit_checkout',
            'attendance',
            "Employee #{$employee->id} check-out. Coords: {$request->lat},{$request->lng}."
        );

        return redirect('/attendance/history')->with('success', 'Check-out berhasil dicatat.');
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
