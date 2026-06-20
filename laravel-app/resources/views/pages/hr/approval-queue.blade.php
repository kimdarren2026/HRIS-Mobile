<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport"/>
<title>HR Approval Queue - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "outline": "#777587",
                    "on-error": "#ffffff",
                    "on-primary-fixed-variant": "#3323cc",
                    "on-tertiary-container": "#ffd2be",
                    "warning": "#F59E0B",
                    "background": "#f9f9ff",
                    "on-surface": "#151c27",
                    "on-primary-container": "#dad7ff",
                    "tertiary-fixed": "#ffdbcc",
                    "on-error-container": "#93000a",
                    "surface": "#F9FAFB",
                    "secondary-fixed": "#e3dfff",
                    "primary-fixed": "#e2dfff",
                    "surface-container-high": "#e2e8f8",
                    "success": "#10B981",
                    "surface-container": "#e7eefe",
                    "inverse-surface": "#2a313d",
                    "error-container": "#ffdad6",
                    "surface-container-low": "#f0f3ff",
                    "danger": "#EF4444",
                    "error": "#ba1a1a",
                    "on-primary-fixed": "#0f0069",
                    "surface-container-variant": "#f1f5f9",
                    "tertiary": "#7e3000",
                    "primary-fixed-dim": "#c3c0ff",
                    "on-secondary-fixed": "#100069",
                    "on-surface-variant": "#464555",
                    "surface-tint": "#4d44e3",
                    "secondary-container": "#6860ef",
                    "inverse-on-surface": "#ebf1ff",
                    "secondary-fixed-dim": "#c3c0ff",
                    "outline-variant": "#c7c4d8",
                    "on-tertiary-fixed-variant": "#7b2f00",
                    "on-background": "#151c27",
                    "tertiary-container": "#a44100",
                    "primary-container": "#4f46e5",
                    "surface-dim": "#d3daea",
                    "primary": "#3525cd",
                    "on-tertiary-fixed": "#351000",
                    "on-secondary": "#ffffff",
                    "on-secondary-fixed-variant": "#372abf",
                    "on-secondary-container": "#fffbff",
                    "on-primary": "#ffffff",
                    "surface-variant": "#dce2f3",
                    "on-tertiary": "#ffffff",
                    "tertiary-fixed-dim": "#ffb695",
                    "inverse-primary": "#c3c0ff",
                    "border": "#E5E7EB",
                    "surface-bright": "#f9f9ff",
                    "surface-container-highest": "#dce2f3",
                    "secondary": "#4e45d5"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "unit-sm": "8px",
                    "unit-lg": "24px",
                    "unit-md": "16px",
                    "unit-xl": "32px",
                    "unit-xs": "4px",
                    "container-margin": "16px",
                    "card-gap": "12px"
            },
            "fontFamily": {
                    "status-badge": ["Inter"],
                    "headline-lg": ["Inter"],
                    "body-lg": ["Inter"],
                    "body-md": ["Inter"],
                    "label-sm": ["Inter"],
                    "label-md": ["Inter"],
                    "headline-md": ["Inter"]
            },
            "fontSize": {
                    "status-badge": ["12px", {"lineHeight": "12px", "fontWeight": "700"}],
                    "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
                    "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                    "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                    "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
                    "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                    "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}]
            }
          },
        },
      }
    </script>
<style>
        body { font-family: 'Inter', sans-serif; background-color: #f9f9ff; -webkit-tap-highlight-color: transparent; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .active-tab-indicator { position: absolute; bottom: 0; left: 0; height: 3px; background-color: #3525cd; border-radius: 3px 3px 0 0; transition: all 0.3s ease; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
<style>
        body {
          min-height: max(884px, 100dvh);
        }
    </style>
</head>
<body class="text-on-background min-h-screen max-w-[390px] mx-auto overflow-x-hidden pb-[72px]">
<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-outline-variant shadow-sm h-16 flex justify-between items-center px-container-margin">
<div class="flex items-center gap-3">
<button class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-colors active:scale-95 duration-150" onclick="window.location.href='/settings'">
<span class="material-symbols-outlined text-on-surface-variant">menu</span>
</button>
<h1 class="font-headline-md text-headline-md font-bold text-primary">HR Approval Queue</h1>
</div>
<!-- TODO Phase 4: connect action -->
<button class="relative w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-colors active:scale-95 duration-150">
<span class="material-symbols-outlined text-on-surface-variant">notifications</span>
<span class="absolute top-2 right-2 w-2 h-2 bg-danger rounded-full"></span>
</button>
</header>
<main class="pt-16 max-w-[390px] mx-auto min-h-screen">
<!-- Tabs Section -->
<div class="bg-surface sticky top-16 z-40">
<div class="flex border-b border-outline-variant">
<button id="tab-btn-attendance" onclick="switchTab('attendance')"
    class="relative flex-1 flex items-center justify-center py-4 text-primary font-bold transition-colors">
<div class="flex items-center gap-2">
<span class="font-label-md text-label-md">Attendance</span>
<span class="bg-primary-container text-on-primary-container px-2 py-0.5 rounded-full text-[10px] font-bold">{{ $pending->total() }}</span>
</div>
<div class="active-tab-indicator w-full"></div>
</button>
<button id="tab-btn-leave" onclick="switchTab('leave')"
    class="relative flex-1 flex items-center justify-center py-4 text-on-surface-variant hover:bg-surface-container-low transition-colors">
<div class="flex items-center gap-2">
<span class="font-label-md text-label-md">Leave</span>
<span class="bg-surface-container-highest text-on-surface-variant px-2 py-0.5 rounded-full text-[10px] font-bold">{{ $leavePending->total() }}</span>
</div>
</button>
</div>
</div>

{{-- Flash --}}
@if(session('success'))
<div class="mx-container-margin mt-unit-md bg-success/10 border border-success/30 text-success rounded-lg px-4 py-3 font-body-md text-body-md flex items-center gap-2">
<span class="material-symbols-outlined text-[18px]">check_circle</span> {{ session('success') }}
</div>
@endif

<!-- Attendance Tab Content -->
<div id="tab-attendance" class="p-container-margin flex flex-col gap-unit-lg">

@forelse($pending as $record)
<div class="bg-surface border border-outline-variant rounded-xl shadow-sm overflow-hidden flex flex-col gap-unit-md p-unit-md">
<div class="flex items-start justify-between">
<div class="flex gap-3">
<div class="w-12 h-12 rounded-full bg-primary-container flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-on-primary-container">person</span>
</div>
<div>
<h3 class="font-headline-md text-[16px] font-bold text-on-surface">{{ $record->employee->user->name ?? '—' }}</h3>
<p class="font-body-md text-on-surface-variant">
{{ $record->employee->department->name ?? '—' }} &bull; {{ $record->check_in_time?->diffForHumans() }}
</p>
</div>
</div>
<div class="bg-warning/10 text-warning px-3 py-1 rounded-full font-status-badge text-status-badge shrink-0">
Pending Review
</div>
</div>
<div class="bg-surface-container-low p-3 rounded-lg border border-outline-variant/30">
<div class="flex items-center gap-2 mb-1">
<span class="material-symbols-outlined text-primary text-[18px]">location_on</span>
<span class="font-label-md text-label-md text-on-surface-variant">
Check-in {{ $record->check_in_time?->format('d M Y, h:i A') }}
</span>
</div>
@if($record->out_of_radius_reason)
<p class="font-body-md text-on-surface-variant mt-2 italic leading-relaxed">"{{ $record->out_of_radius_reason }}"</p>
@endif
@if($record->check_in_photo_path)
<a href="/attendance/photo/{{ $record->id }}" target="_blank"
   class="inline-flex items-center gap-1 mt-2 text-primary font-label-sm text-label-sm hover:underline">
<span class="material-symbols-outlined text-[14px]">photo_camera</span> View selfie
</a>
@endif
</div>
<form method="POST" action="/hr/attendance/{{ $record->id }}/approve" class="flex gap-3 pt-2 flex-col">
@csrf
<input type="text" name="approval_note" placeholder="Optional note for employee..." maxlength="1000"
    class="w-full border border-outline-variant rounded-lg px-3 py-2 font-body-md text-body-md text-on-surface bg-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none placeholder:text-outline">
<div class="flex gap-3">
<button type="submit"
    class="flex-1 bg-success text-on-primary py-3 rounded-lg font-label-md flex items-center justify-center gap-2 active:scale-95 transition-transform">
<span class="material-symbols-outlined text-[18px]">check_circle</span> Approve
</button>
<button type="button" onclick="toggleReject('att-{{ $record->id }}')"
    class="flex-1 border-2 border-danger text-danger py-3 rounded-lg font-label-md flex items-center justify-center gap-2 active:scale-95 transition-transform">
<span class="material-symbols-outlined text-[18px]">block</span> Reject
</button>
</div>
</form>
<form id="reject-form-att-{{ $record->id }}" method="POST" action="/hr/attendance/{{ $record->id }}/reject" class="hidden flex-col gap-2">
@csrf
<textarea name="approval_note" rows="3" maxlength="1000" required minlength="10" placeholder="Rejection reason (required, min 10 characters)..."
    class="w-full border border-danger rounded-lg px-3 py-2 font-body-md text-body-md text-on-surface bg-surface focus:border-error focus:ring-1 focus:ring-error outline-none resize-none placeholder:text-outline"></textarea>
<button type="submit"
    class="w-full bg-danger text-on-error py-3 rounded-lg font-label-md flex items-center justify-center gap-2 active:scale-95 transition-transform">
<span class="material-symbols-outlined text-[18px]">cancel</span> Confirm Rejection
</button>
</form>
</div>
@empty
<div class="flex flex-col items-center justify-center py-unit-xl opacity-40 select-none">
<span class="material-symbols-outlined text-[64px]">rule</span>
<p class="font-label-md mt-2 text-on-surface-variant">No attendance pending review.</p>
</div>
@endforelse

@if($pending->hasPages())
<div class="pb-4">{{ $pending->links() }}</div>
@endif

</div>

<!-- Leave Tab Content -->
<div id="tab-leave" class="hidden p-container-margin flex flex-col gap-unit-lg">

@forelse($leavePending as $leave)
@php
    $days = (int) $leave->total_days;
    $dateRange = $leave->start_date->isSameDay($leave->end_date)
        ? $leave->start_date->format('d M Y')
        : $leave->start_date->format('d M') . ' – ' . $leave->end_date->format('d M Y');
@endphp
<div class="bg-surface border border-outline-variant rounded-xl shadow-sm overflow-hidden flex flex-col gap-unit-md p-unit-md">
<div class="flex items-start justify-between">
<div class="flex gap-3">
<div class="w-12 h-12 rounded-full bg-primary-container flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-on-primary-container">person</span>
</div>
<div>
<h3 class="font-headline-md text-[16px] font-bold text-on-surface">{{ $leave->employee->user->name ?? '—' }}</h3>
<p class="font-body-md text-on-surface-variant">
{{ $leave->employee->department->name ?? '—' }} &bull; {{ $leave->created_at->diffForHumans() }}
</p>
</div>
</div>
<div class="bg-warning/10 text-warning px-3 py-1 rounded-full font-status-badge text-status-badge shrink-0">
Pending HR
</div>
</div>
<div class="bg-surface-container-low p-3 rounded-lg border border-outline-variant/30 flex flex-col gap-1">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-[18px]">event_note</span>
<span class="font-label-md text-label-md text-on-surface-variant">
{{ $leave->leaveType->name }} &bull; {{ $dateRange }} &bull; {{ $days }} {{ $days === 1 ? 'day' : 'days' }}
</span>
</div>
<p class="font-body-md text-on-surface-variant italic leading-relaxed">"{{ $leave->reason }}"</p>
@if($leave->attachment_path)
<a href="/leave/attachment/{{ $leave->id }}" target="_blank"
   class="inline-flex items-center gap-1 mt-1 text-primary font-label-sm text-label-sm hover:underline">
<span class="material-symbols-outlined text-[14px]">attach_file</span> Lihat Dokumen
</a>
@endif
</div>
<form method="POST" action="/hr/leave/{{ $leave->id }}/approve" class="flex gap-3 pt-2 flex-col">
@csrf
<input type="text" name="approval_note" placeholder="Optional note for employee..." maxlength="1000"
    class="w-full border border-outline-variant rounded-lg px-3 py-2 font-body-md text-body-md text-on-surface bg-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none placeholder:text-outline">
<div class="flex gap-3">
<button type="submit"
    class="flex-1 bg-success text-on-primary py-3 rounded-lg font-label-md flex items-center justify-center gap-2 active:scale-95 transition-transform">
<span class="material-symbols-outlined text-[18px]">check_circle</span> Approve
</button>
<button type="button" onclick="toggleReject('leave-{{ $leave->id }}')"
    class="flex-1 border-2 border-danger text-danger py-3 rounded-lg font-label-md flex items-center justify-center gap-2 active:scale-95 transition-transform">
<span class="material-symbols-outlined text-[18px]">block</span> Reject
</button>
</div>
</form>
<form id="reject-form-leave-{{ $leave->id }}" method="POST" action="/hr/leave/{{ $leave->id }}/reject" class="hidden flex-col gap-2">
@csrf
<textarea name="approval_note" rows="3" maxlength="1000" required minlength="10" placeholder="Rejection reason (required, min 10 characters)..."
    class="w-full border border-danger rounded-lg px-3 py-2 font-body-md text-body-md text-on-surface bg-surface focus:border-error focus:ring-1 focus:ring-error outline-none resize-none placeholder:text-outline"></textarea>
<button type="submit"
    class="w-full bg-danger text-on-error py-3 rounded-lg font-label-md flex items-center justify-center gap-2 active:scale-95 transition-transform">
<span class="material-symbols-outlined text-[18px]">cancel</span> Confirm Rejection
</button>
</form>
</div>
@empty
<div class="flex flex-col items-center justify-center py-unit-xl opacity-40 select-none">
<span class="material-symbols-outlined text-[64px]">event_busy</span>
<p class="font-label-md mt-2 text-on-surface-variant">No leave requests pending.</p>
</div>
@endforelse

@if($leavePending->hasPages())
<div class="pb-4">{{ $leavePending->links() }}</div>
@endif

</div>
</main>

<script>
function toggleReject(key) {
    const f = document.getElementById('reject-form-' + key);
    if (!f) return;
    f.classList.toggle('hidden');
    f.classList.toggle('flex');
}

function switchTab(tab) {
    const attPane = document.getElementById('tab-attendance');
    const leavePane = document.getElementById('tab-leave');
    const attBtn = document.getElementById('tab-btn-attendance');
    const leaveBtn = document.getElementById('tab-btn-leave');

    if (tab === 'attendance') {
        attPane.classList.remove('hidden');
        leavePane.classList.add('hidden');
        attBtn.classList.add('text-primary', 'font-bold');
        attBtn.classList.remove('text-on-surface-variant');
        leaveBtn.classList.remove('text-primary', 'font-bold');
        leaveBtn.classList.add('text-on-surface-variant');
        if (!attBtn.querySelector('.active-tab-indicator')) {
            const ind = document.createElement('div');
            ind.className = 'active-tab-indicator w-full';
            attBtn.appendChild(ind);
        }
        leaveBtn.querySelector('.active-tab-indicator')?.remove();
    } else {
        leavePane.classList.remove('hidden');
        attPane.classList.add('hidden');
        leaveBtn.classList.add('text-primary', 'font-bold');
        leaveBtn.classList.remove('text-on-surface-variant');
        attBtn.classList.remove('text-primary', 'font-bold');
        attBtn.classList.add('text-on-surface-variant');
        if (!leaveBtn.querySelector('.active-tab-indicator')) {
            const ind = document.createElement('div');
            ind.className = 'active-tab-indicator w-full';
            leaveBtn.appendChild(ind);
        }
        attBtn.querySelector('.active-tab-indicator')?.remove();
    }
}
</script>
<!-- BottomNavBar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface/80 backdrop-blur-md border-t border-outline-variant shadow-sm h-[72px] flex justify-around items-center px-unit-sm pb-safe">
<!-- Home -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200" onclick="window.location.href='/admin/dashboard'">
<span class="material-symbols-outlined">home</span>
<span class="font-label-md text-[10px] mt-1">Home</span>
</button>
<!-- Employees -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200" onclick="window.location.href='/hr/employees'">
<span class="material-symbols-outlined">groups</span>
<span class="font-label-md text-[10px] mt-1">Employees</span>
</button>
<!-- Approvals (Active) -->
<button class="flex flex-col items-center justify-center bg-secondary-container text-on-secondary-container rounded-xl px-4 py-1.5 active:scale-90 duration-200" onclick="window.location.href='/hr/approval-queue'">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">rule</span>
<span class="font-label-md text-[10px] mt-1">Approvals</span>
</button>
<!-- Reports -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200" onclick="window.location.href='/reports'">
<span class="material-symbols-outlined">assessment</span>
<span class="font-label-md text-[10px] mt-1">Reports</span>
</button>
<!-- Profile -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-3 py-1.5 hover:bg-surface-container transition-all active:scale-90 duration-200" onclick="window.location.href='/profile'">
<span class="material-symbols-outlined">person</span>
<span class="font-label-md text-[10px] mt-1">Profile</span>
</button>
</nav>
<script>
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('touchstart', function() { this.classList.add('opacity-80'); });
        button.addEventListener('touchend', function() { this.classList.remove('opacity-80'); });
    });
</script>
</body></html>
