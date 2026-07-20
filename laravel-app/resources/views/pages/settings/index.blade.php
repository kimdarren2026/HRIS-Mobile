<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>System Settings - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                "primary-fixed-dim": "#c3c0ff",
                "on-secondary-fixed-variant": "#372abf",
                "inverse-on-surface": "#ebf1ff",
                "on-secondary-container": "#fffbff",
                "surface-dim": "#d3daea",
                "on-tertiary-fixed-variant": "#7b2f00",
                "on-surface-variant": "#464555",
                "tertiary": "#7e3000",
                "on-background": "#151c27",
                "on-primary-fixed": "#0f0069",
                "surface-variant": "#dce2f3",
                "success": "#10B981",
                "on-primary": "#ffffff",
                "surface-container-lowest": "#ffffff",
                "secondary": "#4e45d5",
                "primary": "#3525cd",
                "on-primary-fixed-variant": "#3323cc",
                "surface-container-high": "#e2e8f8",
                "tertiary-fixed": "#ffdbcc",
                "tertiary-fixed-dim": "#ffb695",
                "inverse-primary": "#c3c0ff",
                "outline-variant": "#c7c4d8",
                "border": "#E5E7EB",
                "secondary-fixed": "#e3dfff",
                "on-surface": "#151c27",
                "surface-tint": "#4d44e3",
                "on-tertiary-container": "#ffd2be",
                "error-container": "#ffdad6",
                "background": "#f9f9ff",
                "surface-container": "#e7eefe",
                "surface-container-low": "#f0f3ff",
                "surface-bright": "#f9f9ff",
                "secondary-container": "#6860ef",
                "primary-container": "#4f46e5",
                "on-secondary-fixed": "#100069",
                "warning": "#F59E0B",
                "on-secondary": "#ffffff",
                "on-error-container": "#93000a",
                "on-primary-container": "#dad7ff",
                "on-tertiary-fixed": "#351000",
                "on-error": "#ffffff",
                "danger": "#EF4444",
                "error": "#ba1a1a",
                "outline": "#777587",
                "inverse-surface": "#2a313d",
                "primary-fixed": "#e2dfff",
                "secondary-fixed-dim": "#c3c0ff",
                "surface": "#F9FAFB",
                "on-tertiary": "#ffffff",
                "tertiary-container": "#a44100",
                "surface-container-highest": "#dce2f3"
            },
            "borderRadius": {
                "DEFAULT": "0.25rem",
                "lg": "0.5rem",
                "xl": "0.75rem",
                "full": "9999px"
            },
            "spacing": {
                "container-margin": "16px",
                "unit-md": "16px",
                "unit-lg": "24px",
                "unit-sm": "8px",
                "card-gap": "12px",
                "unit-xs": "4px",
                "unit-xl": "32px"
            },
            "fontFamily": {
                "headline-md": ["Inter"],
                "body-md": ["Inter"],
                "body-lg": ["Inter"],
                "status-badge": ["Inter"],
                "label-md": ["Inter"],
                "label-sm": ["Inter"],
                "headline-lg": ["Inter"]
            },
            "fontSize": {
                "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                "headline-md-mobile": ["18px", {"lineHeight": "24px", "fontWeight": "600"}],
                "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                "status-badge": ["12px", {"lineHeight": "12px", "fontWeight": "700"}],
                "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
                "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}]
            }
          },
        },
      }
    </script>
<style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
</head>
<body class="bg-background text-on-surface flex flex-col items-center min-h-screen">
<!-- Top App Bar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-outline-variant shadow-sm flex justify-between items-center px-container-margin h-16 mx-auto">
<button class="p-2 rounded-full hover:bg-surface-container-low transition-colors active:scale-95 duration-150" onclick="window.location.href='/admin/dashboard'">
<span class="material-symbols-outlined text-primary">menu</span>
</button>
<h1 class="font-headline-md text-headline-md-mobile font-bold text-primary">System Settings</h1>
<div class="w-10"></div>
</header>

@if(session('success'))
<div class="fixed top-16 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-40 px-container-margin pt-2">
    <div class="bg-success/10 border border-success/30 text-success rounded-lg px-4 py-3 font-body-md text-body-md flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1;">check_circle</span>
        {{ session('success') }}
    </div>
</div>
@endif

<!-- Main Content Area -->
<main class="w-full max-w-[390px] mt-16 mb-20 px-container-margin flex flex-col gap-unit-md py-unit-md overflow-y-auto hide-scrollbar">
<!-- Section 1: Office Location & Radius -->
<section class="bg-white border border-border rounded-xl shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] p-unit-md flex flex-col gap-unit-sm">
<div class="flex items-center gap-2 text-primary">
<span class="material-symbols-outlined text-[20px]">location_on</span>
<h2 class="font-headline-md text-headline-md-mobile">Office Location &amp; Radius</h2>
</div>
@if($office)
<div class="flex flex-col gap-1">
<div class="flex items-center justify-between">
<span class="font-label-md text-label-md text-on-surface-variant">{{ $office->name }}</span>
<span class="font-status-badge text-status-badge text-success bg-success/10 px-2 py-1 rounded-full flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]" style="font-variation-settings:'FILL' 1;">check_circle</span> Active
</span>
</div>
<p class="font-body-md text-body-md text-on-surface">Lat: {{ $office->latitude }}, Lng: {{ $office->longitude }}</p>
<div class="mt-2 py-2 px-3 bg-surface-container-low rounded-lg flex justify-between items-center">
<span class="font-body-md text-body-md">Check-in radius</span>
<span class="font-status-badge text-status-badge text-primary bg-primary-container/20 px-2 py-1 rounded">{{ $office->radius_meters }} meters</span>
</div>
</div>
<a href="{{ route('settings.locations.edit', $office) }}" class="mt-2 block text-center border border-primary text-primary font-label-md text-label-md py-2.5 rounded-lg active:scale-95 transition-transform hover:bg-primary/5">
    Edit Office Location
</a>
@else
<p class="font-body-md text-body-md text-on-surface-variant">No active office location configured. Employee check-in will be blocked until a location is set.</p>
<a href="{{ route('settings.locations.create') }}" class="mt-2 block text-center bg-primary text-on-primary font-label-md text-label-md py-2.5 rounded-lg active:scale-95 transition-transform hover:bg-primary/90">
    Set Office Location
</a>
@endif
</section>

<!-- Section 2: Leave Settings -->
<section class="bg-white border border-border rounded-xl shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] p-unit-md flex flex-col gap-unit-sm">
<div class="flex items-center gap-2 text-primary">
<span class="material-symbols-outlined text-[20px]">event_busy</span>
<h2 class="font-headline-md text-headline-md-mobile">Leave Settings</h2>
</div>
@if($leaveTypes->isNotEmpty())
<div class="grid grid-cols-2 gap-2">
@foreach($leaveTypes as $leaveType)
<div class="p-3 border border-outline-variant rounded-lg flex flex-col gap-1">
<span class="font-label-sm text-label-sm text-outline">{{ $leaveType->name }}</span>
<span class="font-body-md text-body-md font-semibold">{{ $leaveType->deducts_balance ? 'Balance tracked' : 'No quota' }}</span>
</div>
@endforeach
</div>
@else
<p class="font-body-md text-body-md text-on-surface-variant">No leave types configured.</p>
@endif
<a href="{{ route('settings.leave-types.index') }}" class="mt-2 block text-center border border-primary text-primary font-label-md text-label-md py-2.5 rounded-lg active:scale-95 transition-transform hover:bg-primary/5">
    Manage Leave Types
</a>
</section>

<!-- Section 3: Payroll Settings -->
<section class="bg-white border border-border rounded-xl shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] p-unit-md flex flex-col gap-unit-sm">
<div class="flex items-center gap-2 text-primary">
<span class="material-symbols-outlined text-[20px]">payments</span>
<h2 class="font-headline-md text-headline-md-mobile">Payroll Settings</h2>
</div>
<div class="relative pl-6 flex flex-col gap-3">
<div class="absolute left-2 top-0 bottom-0 w-[2px] bg-outline-variant"></div>
<div class="relative flex items-center gap-3">
<div class="absolute -left-5 w-4 h-4 rounded-full bg-success ring-4 ring-white"></div>
<span class="font-body-md text-body-md font-medium">Payroll workflow</span>
</div>
<div class="flex flex-wrap gap-1.5 mt-1">
<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-surface-container-high text-outline">Draft</span>
<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-surface-container-high text-outline">Calculated</span>
<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-primary-container/20 text-primary">HR Review</span>
<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-surface-container-high text-outline">Finance Approval</span>
<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-surface-container-high text-outline">Locked</span>
<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-surface-container-high text-outline">Paid</span>
</div>
</div>
<div class="mt-2 p-3 bg-surface-container-low rounded-lg flex items-center gap-2 text-on-surface-variant">
<span class="material-symbols-outlined text-[16px]">info</span>
<span class="font-body-md text-body-md">Payroll rules are managed by system configuration</span>
</div>
</section>

<!-- Section 4: User & Role Access -->
<section class="bg-white border border-border rounded-xl shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] p-unit-md flex flex-col gap-unit-sm">
<div class="flex items-center gap-2 text-primary">
<span class="material-symbols-outlined text-[20px]">shield_person</span>
<h2 class="font-headline-md text-headline-md-mobile">User &amp; Role Access</h2>
</div>
<ul class="flex flex-col gap-2">
<li class="flex justify-between items-center py-2 border-b border-outline-variant/30">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center">
<span class="material-symbols-outlined text-on-surface-variant text-[18px]">badge</span>
</div>
<span class="font-body-md text-body-md">Employee</span>
</div>
<span class="font-label-sm text-label-sm text-outline">Self-service</span>
</li>
<li class="flex justify-between items-center py-2 border-b border-outline-variant/30">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-secondary/10 flex items-center justify-center">
<span class="material-symbols-outlined text-secondary text-[18px]">admin_panel_settings</span>
</div>
<span class="font-body-md text-body-md">Admin HR</span>
</div>
<span class="font-label-sm text-label-sm text-outline">HR + self-service</span>
</li>
<li class="flex justify-between items-center py-2 border-b border-outline-variant/30">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center">
<span class="material-symbols-outlined text-on-surface-variant text-[18px]">account_balance</span>
</div>
<span class="font-body-md text-body-md">Finance</span>
</div>
<span class="font-label-sm text-label-sm text-outline">Finance + self-service</span>
</li>
<li class="flex justify-between items-center py-2">
<div class="flex items-center gap-3">
<div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
<span class="material-symbols-outlined text-primary text-[18px]">verified_user</span>
</div>
<span class="font-body-md text-body-md font-semibold">Super Admin</span>
</div>
<span class="font-label-sm text-label-sm text-outline">Full access</span>
</li>
</ul>
<div class="mt-2 p-3 bg-surface-container-low rounded-lg flex items-center gap-2 text-on-surface-variant">
<span class="material-symbols-outlined text-[16px]">info</span>
<span class="font-body-md text-body-md">Role management is handled by system administration</span>
</div>
@if(auth()->user()?->role === 'super_admin')
<a href="{{ route('admin.users.index') }}"
   class="mt-1 flex items-center justify-between p-3 bg-primary/5 border border-primary/20 rounded-lg hover:bg-primary/10 active:scale-95 transition-transform">
  <div class="flex items-center gap-2 text-primary">
    <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
    <span class="font-body-md text-body-md font-semibold">Manage Users & Roles</span>
  </div>
  <span class="material-symbols-outlined text-primary text-[18px]">chevron_right</span>
</a>
@endif
</section>

<!-- Section 5: Notification Settings -->
<section class="bg-white border border-border rounded-xl shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] p-unit-md flex flex-col gap-unit-sm">
<div class="flex items-center gap-2 text-primary">
<span class="material-symbols-outlined text-[20px]">notifications_active</span>
<h2 class="font-headline-md text-headline-md-mobile">Notification Settings</h2>
</div>
<div class="flex flex-col gap-4 mt-2">
<label class="flex items-center justify-between cursor-pointer">
<span class="font-body-md text-body-md">Attendance approval</span>
<div class="relative inline-block w-10 h-6 align-middle select-none transition duration-200 ease-in">
<input checked="" class="sr-only peer" id="toggle-1" name="toggle" type="checkbox"/>
<div class="w-full h-full bg-outline-variant peer-checked:bg-primary rounded-full transition-colors"></div>
<div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
</div>
</label>
<label class="flex items-center justify-between cursor-pointer">
<span class="font-body-md text-body-md">Leave approval</span>
<div class="relative inline-block w-10 h-6 align-middle select-none transition duration-200 ease-in">
<input checked="" class="sr-only peer" id="toggle-2" name="toggle" type="checkbox"/>
<div class="w-full h-full bg-outline-variant peer-checked:bg-primary rounded-full transition-colors"></div>
<div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
</div>
</label>
<label class="flex items-center justify-between cursor-pointer">
<span class="font-body-md text-body-md">Payslip available</span>
<div class="relative inline-block w-10 h-6 align-middle select-none transition duration-200 ease-in">
<input class="sr-only peer" id="toggle-3" name="toggle" type="checkbox"/>
<div class="w-full h-full bg-outline-variant peer-checked:bg-primary rounded-full transition-colors"></div>
<div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-4 shadow-sm"></div>
</div>
</label>
</div>
</section>

<!-- Section 6: Security Settings -->
<section class="bg-white border border-border rounded-xl shadow-[0px_1px_2px_0px_rgba(0,0,0,0.05)] p-unit-md flex flex-col gap-unit-sm">
<div class="flex items-center gap-2 text-primary">
<span class="material-symbols-outlined text-[20px]">security</span>
<h2 class="font-headline-md text-headline-md-mobile">Security Settings</h2>
</div>
<div class="grid grid-cols-1 gap-3 mt-1">
<div class="flex items-center justify-between p-3 bg-surface rounded-lg border border-outline-variant/20">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-primary">location_searching</span>
<span class="font-body-md text-body-md">GPS validation</span>
</div>
<div class="flex items-center gap-1.5 text-success">
<span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">check_circle</span>
<span class="font-status-badge text-status-badge">Enabled</span>
</div>
</div>
<div class="flex items-center justify-between p-3 bg-surface rounded-lg border border-outline-variant/20">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-primary">face</span>
<span class="font-body-md text-body-md">Selfie required</span>
</div>
<div class="flex items-center gap-1.5 text-success">
<span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">check_circle</span>
<span class="font-status-badge text-status-badge">Active</span>
</div>
</div>
<div class="flex items-center justify-between p-3 bg-surface rounded-lg border border-outline-variant/20">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-primary">history_edu</span>
<span class="font-body-md text-body-md">Audit log enabled</span>
</div>
<div class="flex items-center gap-1.5 text-success">
<span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">check_circle</span>
<span class="font-status-badge text-status-badge">Active</span>
</div>
</div>
<div class="flex items-center justify-between p-3 bg-surface rounded-lg border border-outline-variant/20">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-primary">visibility_off</span>
<span class="font-body-md text-body-md">Data masking enabled</span>
</div>
<div class="flex items-center gap-1.5 text-success">
<span class="material-symbols-outlined text-[16px]" style="font-variation-settings: 'FILL' 1;">check_circle</span>
<span class="font-status-badge text-status-badge">Active</span>
</div>
</div>
</div>
</section>
</main>

<!-- Bottom Navigation Bar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 h-18 pb-safe bg-surface/80 backdrop-blur-md border-t border-outline-variant flex justify-around items-center mx-auto py-2">
<a class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary transition-all active:scale-90 duration-200" href="/admin/dashboard">
<span class="material-symbols-outlined">home</span>
<span class="font-label-sm text-label-sm mt-1">Home</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary transition-all active:scale-90 duration-200" href="/employees">
<span class="material-symbols-outlined">group</span>
<span class="font-label-sm text-label-sm mt-1">Employees</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary transition-all active:scale-90 duration-200" href="/hr/approval-queue">
<span class="material-symbols-outlined">rule</span>
<span class="font-label-sm text-label-sm mt-1">Approvals</span>
</a>
<a class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary transition-all active:scale-90 duration-200" href="/reports">
<span class="material-symbols-outlined">assessment</span>
<span class="font-label-sm text-label-sm mt-1">Reports</span>
</a>
<a class="flex flex-col items-center justify-center text-primary bg-primary-container/20 rounded-xl px-3 py-1 active:scale-90 duration-200" href="/settings">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">settings</span>
<span class="font-label-sm text-label-sm mt-1">Settings</span>
</a>
</nav>
</body></html>
