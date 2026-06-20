<!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>HRIS Mobile App Dashboard</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "tertiary-fixed-dim": "#ffb695",
                    "secondary": "#4e45d5",
                    "secondary-fixed": "#e3dfff",
                    "on-tertiary-container": "#ffd2be",
                    "tertiary-fixed": "#ffdbcc",
                    "surface-dim": "#d3daea",
                    "on-secondary": "#ffffff",
                    "surface-container-lowest": "#ffffff",
                    "success": "#10B981",
                    "surface-container-high": "#e2e8f8",
                    "on-tertiary-fixed-variant": "#7b2f00",
                    "on-primary": "#ffffff",
                    "inverse-surface": "#2a313d",
                    "on-secondary-container": "#fffbff",
                    "inverse-primary": "#c3c0ff",
                    "background": "#f9f9ff",
                    "surface-tint": "#4d44e3",
                    "on-surface-variant": "#464555",
                    "on-secondary-fixed-variant": "#372abf",
                    "on-background": "#151c27",
                    "surface-container": "#e7eefe",
                    "surface-container-highest": "#dce2f3",
                    "primary-fixed": "#e2dfff",
                    "on-primary-container": "#dad7ff",
                    "on-secondary-fixed": "#100069",
                    "on-error": "#ffffff",
                    "on-tertiary": "#ffffff",
                    "on-surface": "#151c27",
                    "inverse-on-surface": "#ebf1ff",
                    "primary-container": "#4f46e5",
                    "surface-container-low": "#f0f3ff",
                    "error-container": "#ffdad6",
                    "tertiary-container": "#a44100",
                    "surface-variant": "#dce2f3",
                    "secondary-container": "#6860ef",
                    "border": "#E5E7EB",
                    "on-primary-fixed": "#0f0069",
                    "primary": "#3525cd",
                    "on-primary-fixed-variant": "#3323cc",
                    "secondary-fixed-dim": "#c3c0ff",
                    "danger": "#EF4444",
                    "surface-bright": "#f9f9ff",
                    "surface": "#F9FAFB",
                    "on-tertiary-fixed": "#351000",
                    "tertiary": "#7e3000",
                    "outline": "#777587",
                    "outline-variant": "#c7c4d8",
                    "warning": "#F59E0B",
                    "on-error-container": "#93000a",
                    "primary-fixed-dim": "#c3c0ff",
                    "error": "#ba1a1a"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "unit-lg": "24px",
                    "card-gap": "12px",
                    "container-margin": "16px",
                    "unit-xl": "32px",
                    "unit-xs": "4px",
                    "unit-sm": "8px",
                    "unit-md": "16px"
            },
            "fontFamily": {
                    "label-md": [
                            "Inter"
                    ],
                    "status-badge": [
                            "Inter"
                    ],
                    "label-sm": [
                            "Inter"
                    ],
                    "body-md": [
                            "Inter"
                    ],
                    "headline-lg": [
                            "Inter"
                    ],
                    "headline-md": [
                            "Inter"
                    ],
                    "body-lg": [
                            "Inter"
                    ]
            },
            "fontSize": {
                    "label-md": [
                            "12px",
                            {
                                    "lineHeight": "16px",
                                    "letterSpacing": "0.05em",
                                    "fontWeight": "600"
                            }
                    ],
                    "status-badge": [
                            "12px",
                            {
                                    "lineHeight": "12px",
                                    "fontWeight": "700"
                            }
                    ],
                    "label-sm": [
                            "11px",
                            {
                                    "lineHeight": "14px",
                                    "fontWeight": "500"
                            }
                    ],
                    "body-md": [
                            "14px",
                            {
                                    "lineHeight": "20px",
                                    "fontWeight": "400"
                            }
                    ],
                    "headline-lg": [
                            "24px",
                            {
                                    "lineHeight": "32px",
                                    "fontWeight": "700"
                            }
                    ],
                    "headline-md": [
                            "20px",
                            {
                                    "lineHeight": "28px",
                                    "fontWeight": "600"
                            }
                    ],
                    "body-lg": [
                            "16px",
                            {
                                    "lineHeight": "24px",
                                    "fontWeight": "400"
                            }
                    ]
            }
          }
        }
      }
    </script>
<style>
        .material-symbols-outlined {
          font-variation-settings:
          'FILL' 0,
          'wght' 400,
          'GRAD' 0,
          'opsz' 24;
        }
        .material-symbols-outlined[data-weight="fill"] {
            font-variation-settings: 'FILL' 1;
        }
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>
</head>
<body class="bg-background min-h-screen text-on-background pb-24 max-w-[390px] mx-auto relative overflow-x-hidden border-x border-border">
<!-- Top Navigation (Mobile Header) -->
<header class="flex justify-between items-center px-container-margin py-4 sticky top-0 bg-background/90 backdrop-blur-md z-40">
<div class="flex items-center gap-3">
<img alt="Profile Picture" class="w-10 h-10 rounded-full object-cover border-2 border-surface-container" data-alt="A professional headshot of a young male employee with dark hair, wearing a casual light blue button-down shirt. The background is a soft, blurred modern office environment with bright natural lighting. The mood is approachable, dependable, and efficient." src="https://lh3.googleusercontent.com/aida-public/AB6AXuDxpSLF5Un9emZdClalmC9VGRITCLVYJs-A0IZ-Xfkr-tBR4cMbJvCHrLRpH9I_qJ_Z0x1yMBf75SNA4L2JueltuC2Q8fYXDSpkimM60flB2ZX75Y9eaa9RGi_OIoTEkEbszVcvKE4nUPG5Vx-Pq4WVZDhLnIVShDpMFBRm7zFRpMCT8IxvhBGhVETHw4bLb-IuV0a9shGt7iQ2YnpBi65m2Np-2a3pzL3KGq1I994o71iy2NJSIL9_7v_ZILBHTaZiLrfcvZPmnyU">
<div>
<p class="font-label-sm text-label-sm text-on-surface-variant">Good morning,</p>
<h1 class="font-headline-md text-headline-md text-on-background">Hi, {{ auth()->user()->name }}</h1>
</div>
</div>
<!-- TODO Phase 4: connect action -->
<button class="relative p-2 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors">
<span class="material-symbols-outlined">notifications</span>
<span class="absolute top-2 right-2 w-2 h-2 bg-danger rounded-full border border-background"></span>
</button>
</header>
<!-- Main Content -->
<main class="px-container-margin flex flex-col gap-unit-lg">
<!-- Attendance Card -->
<section class="bg-surface-container-lowest rounded-xl p-unit-md shadow-sm border border-border">
<div class="flex justify-between items-start mb-4">
<div>
<h2 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-1">Today's Attendance</h2>
<p class="font-headline-lg text-headline-lg text-on-background">
@if ($todayRecord)
    {{ $todayRecord->check_in_time?->format('h:i A') ?? '—' }}
@else
    —
@endif
</p>
</div>
@if ($todayRecord)
    @php
    $badgeClass = match($todayRecord->status) {
        'APPROVED'       => 'bg-success/10 text-success',
        'REJECTED'       => 'bg-danger/10 text-danger',
        default          => 'bg-surface-variant text-on-surface-variant',
    };
    $badgeIcon = match($todayRecord->status) {
        'APPROVED' => 'check_circle',
        'REJECTED' => 'cancel',
        default    => 'history',
    };
    @endphp
    <div class="{{ $badgeClass }} px-3 py-1 rounded-full font-status-badge text-status-badge flex items-center gap-1">
    <span class="material-symbols-outlined text-[14px]">{{ $badgeIcon }}</span>
    {{ str_replace('_', ' ', $todayRecord->status) }}
    </div>
@else
    <div class="bg-surface-variant text-on-surface-variant px-3 py-1 rounded-full font-status-badge text-status-badge flex items-center gap-1">
    <span class="material-symbols-outlined text-[14px]">history</span>
    Not Checked In
    </div>
@endif
</div>
@if (!$todayRecord)
<a class="w-full bg-primary-container text-on-primary font-label-md text-label-md py-3 rounded-lg shadow-sm active:scale-95 transition-transform duration-150 flex items-center justify-center gap-2" href="/attendance/checkin">
<span class="material-symbols-outlined" data-weight="fill">location_on</span>
Check In Now
</a>
@else
<a class="w-full bg-surface-container text-on-surface font-label-md text-label-md py-3 rounded-lg flex items-center justify-center gap-2" href="/attendance/history">
<span class="material-symbols-outlined">schedule</span>
View Attendance History
</a>
@endif
</section>
<!-- Quick Stats Bento Grid -->
<section class="grid grid-cols-2 gap-card-gap">
<a class="bg-surface-container-lowest rounded-xl p-unit-md shadow-sm border border-border flex flex-col justify-between" href="/leave/history">
<div class="flex justify-between items-center mb-2">
<span class="material-symbols-outlined text-secondary text-[24px]">event_note</span>
<span class="bg-surface-variant text-on-surface-variant px-2 py-0.5 rounded text-[10px] font-bold">Leave</span>
</div>
<div>
<p class="font-headline-lg text-headline-lg text-on-background mb-0.5">{{ $leaveRemaining }}</p>
<p class="font-label-sm text-label-sm text-on-surface-variant">Remaining Days</p>
</div>
</a>
<a class="bg-surface-container-lowest rounded-xl p-unit-md shadow-sm border border-border flex flex-col justify-between" href="/leave/history">
<div class="flex justify-between items-center mb-2">
<span class="material-symbols-outlined text-warning text-[24px]">pending_actions</span>
</div>
<div>
<p class="font-headline-lg text-headline-lg text-on-background mb-0.5">{{ $pendingLeaveCount }}</p>
<p class="font-label-sm text-label-sm text-on-surface-variant">Pending Requests</p>
</div>
</a>
</section>
<!-- Latest Leave Request -->
<section class="bg-surface-container-lowest rounded-xl p-unit-md shadow-sm border border-border">
<div class="flex justify-between items-center mb-3">
<h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Latest Request</h3>
<a class="text-primary font-label-md text-label-md hover:underline" href="/leave/history">View All</a>
</div>
@if ($latestLeave)
@php
$leaveStatusClass = match($latestLeave->status) {
    'APPROVED'   => 'bg-success/10 text-success',
    'REJECTED'   => 'bg-danger/10 text-danger',
    default      => 'bg-warning/10 text-warning',
};
@endphp
<div class="flex items-center gap-3">
<div class="bg-surface-container w-10 h-10 rounded-lg flex items-center justify-center text-primary">
<span class="material-symbols-outlined">event_note</span>
</div>
<div class="flex-1 min-w-0">
<p class="font-body-md text-body-md font-semibold text-on-background truncate">{{ $latestLeave->leaveType?->name ?? 'Leave Request' }}</p>
<p class="font-label-sm text-label-sm text-on-surface-variant">{{ $latestLeave->start_date->format('M d') }} - {{ $latestLeave->end_date->format('M d, Y') }}</p>
</div>
<div class="{{ $leaveStatusClass }} px-2 py-1 rounded-full font-status-badge text-status-badge shrink-0">
{{ str_replace('PENDING_HR', 'PENDING', $latestLeave->status) }}
</div>
</div>
@else
<p class="font-body-md text-body-md text-on-surface-variant text-center py-2">No leave requests yet.</p>
<a class="mt-2 block w-full text-center text-primary font-label-md text-label-md hover:underline" href="/leave/request">Submit a Request</a>
@endif
</section>
<!-- Payslip Summary -->
<section class="bg-surface-container-lowest rounded-xl p-unit-md shadow-sm border border-border relative overflow-hidden">
<div class="absolute -right-8 -top-8 w-32 h-32 bg-primary-container/5 rounded-full blur-2xl pointer-events-none"></div>
<div class="flex justify-between items-center mb-4 relative z-10">
<h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Latest Payslip</h3>
<span class="font-label-sm text-label-sm text-on-surface-variant">{{ $latestPayroll?->payrollPeriod?->name ?? '' }}</span>
</div>
@if ($latestPayroll)
<div class="flex justify-between items-end relative z-10">
<div>
<p class="font-body-md text-body-md text-on-background font-semibold">Net Pay</p>
<p class="font-headline-md text-headline-md text-on-background">{{ number_format((float) $latestPayroll->net_salary, 2) }}</p>
</div>
<a class="text-primary font-label-md text-label-md flex items-center gap-1 hover:underline active:opacity-70 transition-opacity" href="/my/payroll/{{ $latestPayroll->id }}">
View Details
<span class="material-symbols-outlined text-[16px]">arrow_forward</span>
</a>
</div>
@else
<div class="flex justify-between items-end relative z-10">
<div>
<p class="font-body-md text-body-md text-on-background font-semibold">Net Pay</p>
<p class="font-body-md text-body-md text-on-surface-variant">Not available yet</p>
</div>
<a class="text-primary font-label-md text-label-md flex items-center gap-1 hover:underline active:opacity-70 transition-opacity" href="/my/payroll">
View All
<span class="material-symbols-outlined text-[16px]">arrow_forward</span>
</a>
</div>
@endif
</section>
</main>
<!-- Bottom Navigation Bar (Shared Component) -->
<nav class="bg-surface fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center px-2 py-3 border-t border-border backdrop-blur-md mx-auto">
<!-- Active Tab: Home -->
<a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1 active:scale-90 transition-all duration-200" href="/employee/dashboard">
<span class="material-symbols-outlined" data-weight="fill">home</span>
<span class="font-label-sm text-label-sm mt-0.5">Home</span>
</a>
<!-- Inactive Tabs -->
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-highest rounded-full active:scale-90 transition-all duration-200" href="/attendance/checkin">
<span class="material-symbols-outlined">schedule</span>
<span class="font-label-sm text-label-sm mt-0.5">Attendance</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-highest rounded-full active:scale-90 transition-all duration-200" href="/leave/history">
<span class="material-symbols-outlined">event_note</span>
<span class="font-label-sm text-label-sm mt-0.5">Leave</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-highest rounded-full active:scale-90 transition-all duration-200" href="/my/payroll">
<span class="material-symbols-outlined">payments</span>
<span class="font-label-sm text-label-sm mt-0.5">Payslip</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-highest rounded-full active:scale-90 transition-all duration-200" href="/profile">
<span class="material-symbols-outlined">person</span>
<span class="font-label-sm text-label-sm mt-0.5">Profile</span>
</a>
</nav>
</body></html>
