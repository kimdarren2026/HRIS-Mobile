<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>HRIS Mobile App - Admin Dashboard</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        body {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #dce2f3;
            border-radius: 10px;
        }
    </style>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "surface-container-lowest": "#ffffff",
                        "outline": "#777587",
                        "on-error-container": "#93000a",
                        "tertiary-fixed-dim": "#ffb695",
                        "on-secondary-fixed": "#100069",
                        "tertiary-fixed": "#ffdbcc",
                        "on-secondary-fixed-variant": "#372abf",
                        "surface-dim": "#d3daea",
                        "surface-container-low": "#f0f3ff",
                        "primary-fixed": "#e2dfff",
                        "on-primary-fixed-variant": "#3323cc",
                        "inverse-surface": "#2a313d",
                        "on-surface": "#151c27",
                        "on-error": "#ffffff",
                        "secondary": "#4e45d5",
                        "secondary-fixed": "#e3dfff",
                        "inverse-primary": "#c3c0ff",
                        "surface-tint": "#4d44e3",
                        "primary": "#3525cd",
                        "surface-container": "#e7eefe",
                        "secondary-fixed-dim": "#c3c0ff",
                        "error-container": "#ffdad6",
                        "outline-variant": "#c7c4d8",
                        "on-primary-container": "#dad7ff",
                        "error": "#ba1a1a",
                        "border": "#E5E7EB",
                        "surface": "#F9FAFB",
                        "on-secondary": "#ffffff",
                        "on-background": "#151c27",
                        "surface-container-high": "#e2e8f8",
                        "surface-bright": "#f9f9ff",
                        "on-tertiary": "#ffffff",
                        "on-primary": "#ffffff",
                        "on-surface-variant": "#464555",
                        "success": "#10B981",
                        "warning": "#F59E0B",
                        "danger": "#EF4444",
                        "background": "#f9f9ff",
                        "primary-fixed-dim": "#c3c0ff",
                        "on-primary-fixed": "#0f0069",
                        "surface-container-highest": "#dce2f3",
                        "inverse-on-surface": "#ebf1ff",
                        "on-tertiary-container": "#ffd2be",
                        "surface-variant": "#dce2f3",
                        "on-tertiary-fixed-variant": "#7b2f00",
                        "on-secondary-container": "#fffbff",
                        "primary-container": "#4f46e5",
                        "tertiary": "#7e3000",
                        "on-tertiary-fixed": "#351000",
                        "tertiary-container": "#a44100",
                        "secondary-container": "#6860ef"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "unit-lg": "24px",
                        "unit-xl": "32px",
                        "unit-md": "16px",
                        "container-margin": "16px",
                        "unit-sm": "8px",
                        "unit-xs": "4px",
                        "card-gap": "12px"
                    },
                    "fontFamily": {
                        "label-sm": ["Inter"],
                        "headline-md": ["Inter"],
                        "body-md": ["Inter"],
                        "label-md": ["Inter"],
                        "status-badge": ["Inter"],
                        "body-lg": ["Inter"],
                        "headline-lg": ["Inter"]
                    },
                    "fontSize": {
                        "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
                        "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                        "status-badge": ["12px", {"lineHeight": "12px", "fontWeight": "700"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "700"}]
                    }
                },
            },
        }
    </script>
</head>
<body class="bg-background text-on-surface min-h-screen max-w-[390px] mx-auto overflow-x-hidden pb-24">
<!-- Top AppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface-bright border-b border-outline-variant shadow-sm flex justify-between items-center h-16 px-container-margin">
<button class="material-symbols-outlined text-primary p-2 active:opacity-80 transition-opacity hover:bg-surface-container rounded-full" data-icon="menu" onclick="window.location.href='/settings'">menu</button>
<div class="flex flex-col items-center">
<span class="font-headline-lg text-headline-lg text-primary tracking-tight">HRIS Mobile App</span>
</div>
	@include('partials.notification-bell', [
	    'class' => 'relative text-primary p-2 active:opacity-80 transition-opacity hover:bg-surface-container rounded-full',
	    'badgeClass' => 'absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-error text-white text-[10px] font-bold flex items-center justify-center',
	])
</header>
<main class="mt-20 px-container-margin space-y-6">
<!-- Hero Section -->
<section class="space-y-1">
<h1 class="font-headline-md text-headline-md text-on-surface">Hi, {{ auth()->user()->name }}</h1>
<p class="font-body-md text-body-md text-outline">{{ now()->format('l, F d, Y') }}</p>
</section>
<!-- Summary Grid (2x2) -->
<section class="grid grid-cols-2 gap-card-gap">
<div class="bg-surface-container-lowest p-unit-md rounded-xl border border-border shadow-sm flex flex-col gap-1">
<span class="material-symbols-outlined text-primary mb-2" data-icon="group">group</span>
<span class="font-label-md text-label-md text-outline uppercase tracking-wider">Total Employees</span>
<span class="font-headline-lg text-headline-lg text-on-surface">{{ $totalEmployees }}</span>
</div>
<div class="bg-surface-container-lowest p-unit-md rounded-xl border border-border shadow-sm flex flex-col gap-1">
<span class="material-symbols-outlined text-warning mb-2" data-icon="how_to_reg">how_to_reg</span>
<span class="font-label-md text-label-md text-outline uppercase tracking-wider">Pending Attendance</span>
<span class="font-headline-lg text-headline-lg text-on-surface">{{ $pendingAttendance }}</span>
</div>
<div class="bg-surface-container-lowest p-unit-md rounded-xl border border-border shadow-sm flex flex-col gap-1">
<span class="material-symbols-outlined text-tertiary mb-2" data-icon="event_busy">event_busy</span>
<span class="font-label-md text-label-md text-outline uppercase tracking-wider">Leave Requests</span>
<span class="font-headline-lg text-headline-lg text-on-surface">{{ $pendingLeave }}</span>
</div>
<div class="bg-surface-container-lowest p-unit-md rounded-xl border border-border shadow-sm flex flex-col gap-1">
<span class="material-symbols-outlined text-success mb-2" data-icon="payments">payments</span>
<span class="font-label-md text-label-md text-outline uppercase tracking-wider">Payroll Period</span>
<span class="font-body-md text-body-md font-bold text-on-surface truncate">{{ $latestPeriod?->name ?? 'N/A' }}</span>
</div>
</section>
<!-- Quick Actions -->
<section class="space-y-unit-sm">
<h2 class="font-label-md text-label-md text-outline px-1">QUICK ACTIONS</h2>
<div class="grid grid-cols-2 gap-card-gap">
<button class="flex items-center gap-3 bg-primary text-white p-unit-md rounded-xl active:scale-95 transition-transform text-left" onclick="window.location.href='/hr/approval-queue'">
<span class="material-symbols-outlined" data-icon="fact_check">fact_check</span>
<span class="font-label-md text-label-md">Review Attendance</span>
</button>
<button class="flex items-center gap-3 bg-secondary text-white p-unit-md rounded-xl active:scale-95 transition-transform text-left" onclick="window.location.href='/hr/approval-queue'">
<span class="material-symbols-outlined" data-icon="edit_calendar">edit_calendar</span>
<span class="font-label-md text-label-md">Review Leave</span>
</button>
<button class="flex items-center gap-3 bg-surface-container-high text-on-surface p-unit-md rounded-xl active:scale-95 transition-transform text-left border border-outline-variant" onclick="window.location.href='/hr/employees'">
<span class="material-symbols-outlined text-primary" data-icon="manage_accounts">manage_accounts</span>
<span class="font-label-md text-label-md">Manage Employees</span>
</button>
<button class="flex items-center gap-3 bg-surface-container-high text-on-surface p-unit-md rounded-xl active:scale-95 transition-transform text-left border border-outline-variant" onclick="window.location.href='/reports'">
<span class="material-symbols-outlined text-primary" data-icon="analytics">analytics</span>
<span class="font-label-md text-label-md">View Reports</span>
</button>
</div>
</section>
<!-- Attendance Overview (Chart) -->
<section class="bg-surface-container-lowest p-unit-md rounded-xl border border-border shadow-sm space-y-4">
<div class="flex justify-between items-center">
<h2 class="font-headline-md text-headline-md text-on-surface">Attendance Overview</h2>
<span class="material-symbols-outlined text-outline" data-icon="more_vert">more_vert</span>
</div>
<!-- Simple Visual Bar Chart -->
<div class="flex items-end justify-between h-32 px-2 gap-4">
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary rounded-t-lg" style="height: 85%;"></div>
<span class="text-[10px] text-outline font-bold">MON</span>
</div>
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary rounded-t-lg" style="height: 92%;"></div>
<span class="text-[10px] text-outline font-bold">TUE</span>
</div>
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary rounded-t-lg" style="height: 78%;"></div>
<span class="text-[10px] text-outline font-bold">WED</span>
</div>
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-primary rounded-t-lg" style="height: 95%;"></div>
<span class="text-[10px] text-outline font-bold">THU</span>
</div>
<div class="flex flex-col items-center flex-1 gap-2">
<div class="w-full bg-secondary rounded-t-lg opacity-80" style="height: 60%;"></div>
<span class="text-[10px] text-primary font-bold">FRI</span>
</div>
</div>
<div class="grid grid-cols-3 gap-2 pt-2 border-t border-outline-variant">
<div class="text-center">
<p class="text-[10px] text-outline font-bold uppercase">Approved</p>
<p class="font-headline-md text-success">{{ $approvedAttendance }}</p>
</div>
<div class="text-center border-x border-outline-variant">
<p class="text-[10px] text-outline font-bold uppercase">Pending</p>
<p class="font-headline-md text-warning">{{ $pendingAttendance }}</p>
</div>
<div class="text-center">
<p class="text-[10px] text-outline font-bold uppercase">Rejected</p>
<p class="font-headline-md text-danger">{{ $rejectedAttendance }}</p>
</div>
</div>
</section>
<!-- Leave Overview -->
<section class="bg-surface-container-lowest p-unit-md rounded-xl border border-border shadow-sm space-y-4">
<div class="flex justify-between items-center">
<h2 class="font-headline-md text-headline-md text-on-surface">Leave Requests</h2>
<span class="text-primary font-label-md">Weekly</span>
</div>
<div class="flex flex-col gap-3">
<div class="flex items-center justify-between p-3 bg-surface rounded-lg">
<div class="flex items-center gap-3">
<div class="w-2 h-8 bg-warning rounded-full"></div>
<span class="font-body-md font-medium">Pending HR Approval</span>
</div>
<span class="font-headline-md text-on-surface">{{ $pendingLeave }}</span>
</div>
<div class="flex items-center justify-between p-3 bg-surface rounded-lg">
<div class="flex items-center gap-3">
<div class="w-2 h-8 bg-success rounded-full"></div>
<span class="font-body-md font-medium">Approved</span>
</div>
<span class="font-headline-md text-on-surface">{{ $approvedLeave }}</span>
</div>
<div class="flex items-center justify-between p-3 bg-surface rounded-lg">
<div class="flex items-center gap-3">
<div class="w-2 h-8 bg-danger rounded-full"></div>
<span class="font-body-md font-medium">Rejected</span>
</div>
<span class="font-headline-md text-on-surface">{{ $rejectedLeave }}</span>
</div>
</div>
</section>
<!-- Recent Activity -->
<section class="bg-surface-container-lowest p-unit-md rounded-xl border border-border shadow-sm space-y-4 mb-8">
<div class="flex justify-between items-center">
<h2 class="font-headline-md text-headline-md text-on-surface">Recent Activity</h2>
<button class="text-primary font-label-md hover:underline" onclick="window.location.href='/hr/approval-queue'">View All</button>
</div>
<div class="space-y-4">
<!-- Activity Item -->
<div class="flex gap-4">
<div class="w-10 h-10 rounded-full bg-primary-container/20 flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-primary text-sm" data-icon="check_circle">check_circle</span>
</div>
<div class="flex flex-col">
<span class="font-body-md text-on-surface">Attendance approved for <b>Alex Rivers</b></span>
<span class="text-[11px] text-outline">2 mins ago</span>
</div>
</div>
<!-- Activity Item -->
<div class="flex gap-4">
<div class="w-10 h-10 rounded-full bg-secondary-container/20 flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-secondary text-sm" data-icon="description">description</span>
</div>
<div class="flex flex-col">
<span class="font-body-md text-on-surface">Leave request submitted by <b>Sarah Chen</b></span>
<span class="text-[11px] text-outline">1 hour ago</span>
</div>
</div>
<!-- Activity Item -->
<div class="flex gap-4">
<div class="w-10 h-10 rounded-full bg-surface-container-highest flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-outline text-sm" data-icon="person_add">person_add</span>
</div>
<div class="flex flex-col">
<span class="font-body-md text-on-surface">Employee data updated for <b>John Doe</b></span>
<span class="text-[11px] text-outline">3 hours ago</span>
</div>
</div>
<!-- Activity Item -->
<div class="flex gap-4">
<div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-success text-sm" data-icon="payments">payments</span>
</div>
<div class="flex flex-col">
<span class="font-body-md text-on-surface">Payroll period reviewed for <b>June 2026</b></span>
<span class="text-[11px] text-outline">Yesterday</span>
</div>
</div>
</div>
</section>

@if(auth()->user()->employee)
<!-- Self-Service Section -->
<section class="mx-container-margin bg-white border border-border rounded-xl shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] p-4 flex flex-col gap-3 mb-2">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-[20px]">person</span>
<h2 class="font-semibold text-on-surface" style="font-size:16px;line-height:24px;">My Self-Service</h2>
</div>
<div class="grid grid-cols-2 gap-3">
<a href="/attendance/checkin" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-outline-variant hover:bg-surface-container-low transition-colors active:scale-95">
<span class="material-symbols-outlined text-primary">schedule</span>
<span style="font-size:12px;font-weight:600;color:#464555;">My Attendance</span>
</a>
<a href="/leave/request" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-outline-variant hover:bg-surface-container-low transition-colors active:scale-95">
<span class="material-symbols-outlined text-primary">event_note</span>
<span style="font-size:12px;font-weight:600;color:#464555;">My Leave</span>
</a>
<a href="/my/payroll" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-outline-variant hover:bg-surface-container-low transition-colors active:scale-95">
<span class="material-symbols-outlined text-primary">receipt_long</span>
<span style="font-size:12px;font-weight:600;color:#464555;">My Payslip</span>
</a>
<a href="/my/profile" class="flex flex-col items-center gap-2 p-3 rounded-xl border border-outline-variant hover:bg-surface-container-low transition-colors active:scale-95">
<span class="material-symbols-outlined text-primary">account_circle</span>
<span style="font-size:12px;font-weight:600;color:#464555;">My Profile</span>
</a>
</div>
</section>
@endif
</main>
<!-- Bottom Navigation Bar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface-container-lowest rounded-t-xl border-t border-outline-variant shadow-[0px_-1px_2px_0px_rgba(0,0,0,0.05)] flex justify-around items-center h-[72px] pb-safe px-2">
<!-- Home (Active) -->
<button class="flex flex-col items-center justify-center text-primary font-bold hover:bg-surface-container-low transition-all active:scale-95 duration-200" onclick="window.location.href='/admin/dashboard'">
<span class="material-symbols-outlined" data-icon="home" style="font-variation-settings: 'FILL' 1;">home</span>
<span class="font-label-sm text-label-sm">Home</span>
</button>
<!-- Employees -->
<button class="flex flex-col items-center justify-center text-outline hover:bg-surface-container-low transition-all active:scale-95 duration-200" onclick="window.location.href='/hr/employees'">
<span class="material-symbols-outlined" data-icon="badge">badge</span>
<span class="font-label-sm text-label-sm">Employees</span>
</button>
<!-- Approvals -->
<button class="flex flex-col items-center justify-center text-outline hover:bg-surface-container-low transition-all active:scale-95 duration-200" onclick="window.location.href='/hr/approval-queue'">
<span class="material-symbols-outlined" data-icon="fact_check">fact_check</span>
<span class="font-label-sm text-label-sm">Approvals</span>
</button>
<!-- Reports -->
<button class="flex flex-col items-center justify-center text-outline hover:bg-surface-container-low transition-all active:scale-95 duration-200" onclick="window.location.href='/reports'">
<span class="material-symbols-outlined" data-icon="analytics">analytics</span>
<span class="font-label-sm text-label-sm">Reports</span>
</button>
<!-- Profile -->
<button class="flex flex-col items-center justify-center text-outline hover:bg-surface-container-low transition-all active:scale-95 duration-200" onclick="window.location.href='/profile'">
<span class="material-symbols-outlined" data-icon="person">person</span>
<span class="font-label-sm text-label-sm">Profile</span>
</button>
</nav>
<script>
        // Micro-interaction for feedback on card clicks
        document.querySelectorAll('.bg-surface-container-lowest').forEach(card => {
            card.addEventListener('click', () => {
                card.classList.add('scale-[0.98]');
                setTimeout(() => card.classList.remove('scale-[0.98]'), 150);
            });
        });
    </script>


</body></html>
