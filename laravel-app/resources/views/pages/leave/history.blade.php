<!DOCTYPE html><html lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Riwayat Cuti - HRIS Mobile App</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    "surface-variant": "#dce2f3", "surface-bright": "#f9f9ff",
                    "success": "#10B981", "secondary-fixed": "#e3dfff",
                    "secondary-fixed-dim": "#c3c0ff", "on-error": "#ffffff",
                    "on-secondary": "#ffffff", "warning": "#F59E0B",
                    "on-primary-fixed-variant": "#3323cc", "on-error-container": "#93000a",
                    "tertiary-container": "#a44100", "on-surface": "#151c27",
                    "surface-dim": "#d3daea", "primary-fixed": "#e2dfff",
                    "surface": "#F9FAFB", "on-primary-container": "#dad7ff",
                    "inverse-primary": "#c3c0ff", "secondary": "#4e45d5",
                    "tertiary": "#7e3000", "on-tertiary-container": "#ffd2be",
                    "on-primary": "#ffffff", "primary-container": "#4f46e5",
                    "surface-container-lowest": "#ffffff", "tertiary-fixed": "#ffdbcc",
                    "on-tertiary-fixed-variant": "#7b2f00", "outline-variant": "#c7c4d8",
                    "primary": "#3525cd", "error": "#ba1a1a", "danger": "#EF4444",
                    "on-secondary-fixed-variant": "#372abf", "on-secondary-container": "#fffbff",
                    "on-surface-variant": "#464555", "surface-container-high": "#e2e8f8",
                    "on-secondary-fixed": "#100069", "tertiary-fixed-dim": "#ffb695",
                    "outline": "#777587", "background": "#f9f9ff",
                    "primary-fixed-dim": "#c3c0ff", "border": "#E5E7EB",
                    "on-tertiary": "#ffffff", "surface-container-highest": "#dce2f3",
                    "error-container": "#ffdad6", "on-background": "#151c27",
                    "on-tertiary-fixed": "#351000", "inverse-surface": "#2a313d",
                    "inverse-on-surface": "#ebf1ff", "on-primary-fixed": "#0f0069",
                    "surface-container-low": "#f0f3ff", "secondary-container": "#6860ef",
                    "surface-tint": "#4d44e3", "surface-container": "#e7eefe"
                },
                "borderRadius": { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                "spacing": {
                    "unit-xs": "4px", "unit-md": "16px", "container-margin": "16px",
                    "unit-xl": "32px", "card-gap": "12px", "unit-sm": "8px", "unit-lg": "24px"
                },
                "fontFamily": {
                    "status-badge": ["Inter"], "headline-md": ["Inter"], "body-md": ["Inter"],
                    "label-sm": ["Inter"], "headline-lg": ["Inter"], "label-md": ["Inter"], "body-lg": ["Inter"]
                },
                "fontSize": {
                    "status-badge": ["12px", { "lineHeight": "12px", "fontWeight": "700" }],
                    "headline-md": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                    "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                    "label-sm": ["11px", { "lineHeight": "14px", "fontWeight": "500" }],
                    "headline-lg": ["24px", { "lineHeight": "32px", "fontWeight": "700" }],
                    "label-md": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                    "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }]
                }
            }
        }
    }
</script>
<style>
    body { min-height: max(884px, 100dvh); }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
</head>
<body class="bg-surface font-body-md text-on-surface antialiased min-h-screen flex flex-col mx-auto max-w-[390px] relative pb-[88px]">

<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-between items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm mx-auto">
    <button class="text-on-surface-variant hover:bg-surface-container active:scale-95 transition-transform duration-150 p-2 rounded-full flex items-center justify-center">
        <span class="material-symbols-outlined text-headline-md font-headline-md">menu</span>
    </button>
    <div class="text-headline-md font-headline-md font-bold text-primary whitespace-nowrap truncate">HRIS Mobile App</div>
	    @include('partials.notification-bell', [
	        'class' => 'relative text-on-surface-variant hover:bg-surface-container active:scale-95 transition-transform duration-150 p-2 rounded-full flex items-center justify-center',
	    ])
</header>

<main class="flex-grow pt-[88px] px-container-margin pb-unit-xl">

    <div class="mb-unit-lg">
        <h1 class="font-headline-lg text-headline-lg text-on-surface mb-unit-xs">Riwayat Cuti</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">Pantau pengajuan cuti dan izin Anda.</p>
    </div>

    {{-- Flash --}}
    <x-flash-message />
    @if(session('success') === 'Pengajuan cuti berhasil dikirim.')
    <div class="flex justify-center -mt-unit-sm mb-unit-md">
        <div id="leave-submitted-anim" class="w-28 h-28" aria-hidden="true"></div>
    </div>
    <script src="/assets/lottie/vendor/lottie-web.min.js"></script>
    <script src="/assets/lottie/lottie-helper.js"></script>
    <script>
        mountLottie('leave-submitted-anim', '/assets/lottie/leave-submitted.json', { loop: false, autoplay: true });
    </script>
    @endif

    {{-- Filter Chips --}}
    <div class="flex overflow-x-auto gap-unit-sm mb-unit-lg pb-2 no-scrollbar -mx-container-margin px-container-margin">
        @php
            $filters = [
                null        => 'Semua',
                'PENDING_HR' => 'Menunggu',
                'APPROVED'  => 'Disetujui',
                'REJECTED'  => 'Ditolak',
            ];
        @endphp
        @foreach($filters as $val => $label)
        <a href="{{ $val ? '/leave/history?status='.$val : '/leave/history' }}"
           class="whitespace-nowrap px-4 py-2 rounded-full font-label-md text-label-md flex items-center justify-center
                  {{ $status === $val ? 'bg-primary-container text-on-primary-container' : 'bg-surface-container border border-outline-variant text-on-surface-variant' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- Leave Cards --}}
    <div class="flex flex-col gap-card-gap">
        @forelse($requests as $req)
        @php
            $statusColor = match($req->status) {
                'APPROVED' => 'bg-success',
                'REJECTED' => 'bg-error',
                default    => 'bg-warning',
            };
            $badgeColor = match($req->status) {
                'APPROVED' => 'text-success bg-success/10',
                'REJECTED' => 'text-error bg-error/10',
                default    => 'text-warning bg-warning/10',
            };
            $badgeLabel = match($req->status) {
                'APPROVED' => 'Disetujui',
                'REJECTED' => 'Ditolak',
                default    => 'Menunggu HR',
            };
            $icon = match(true) {
                str_contains(strtolower($req->leaveType->name ?? ''), 'sick')     => 'medical_services',
                str_contains(strtolower($req->leaveType->name ?? ''), 'personal') => 'work_off',
                str_contains(strtolower($req->leaveType->name ?? ''), 'special')  => 'celebration',
                default => 'flight_takeoff',
            };
            $days = (int) $req->total_days;
            $dateRange = $req->start_date->isSameDay($req->end_date)
                ? $req->start_date->format('M d, Y')
                : $req->start_date->format('M d') . ' – ' . $req->end_date->format('M d, Y');
        @endphp
        <div class="bg-white border border-border rounded-xl p-unit-md shadow-sm relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1 {{ $statusColor }}"></div>
            <div class="flex justify-between items-start mb-unit-sm pl-2">
                <div class="flex items-center gap-unit-sm">
                    <div class="p-2 bg-surface-container rounded-lg flex items-center justify-center text-on-surface-variant">
                        <span class="material-symbols-outlined">{{ $icon }}</span>
                    </div>
                    <div>
                        <h3 class="font-label-md text-label-md text-on-surface">{{ $req->leaveType->name }}</h3>
                        <span class="font-status-badge text-status-badge {{ $badgeColor }} px-2 py-1 rounded-full mt-1 inline-block">{{ $badgeLabel }}</span>
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-label-md text-label-md text-on-surface">{{ $days }} Hari</div>
                </div>
            </div>
            <div class="pl-2">
                <p class="font-body-md text-body-md text-on-surface-variant mb-1">{{ $dateRange }}</p>
                <p class="font-body-md text-body-md text-on-surface-variant italic truncate">"{{ $req->reason }}"</p>
                @if($req->approval_note)
                <p class="font-label-sm text-label-sm text-on-surface-variant mt-1 border-t border-outline-variant pt-1">
                    Catatan HR: {{ $req->approval_note }}
                </p>
                @endif
                @if($req->attachment_path)
                <a href="/leave/attachment/{{ $req->id }}" target="_blank"
                   class="inline-flex items-center gap-1 mt-1 text-primary font-label-sm text-label-sm hover:underline">
                    <span class="material-symbols-outlined text-[14px]">attach_file</span> Lihat Lampiran
                </a>
                @endif
            </div>
        </div>
        @empty
        <div class="flex flex-col items-center justify-center py-unit-xl select-none">
            <div id="leave-empty-anim" class="w-32 h-32" aria-hidden="true"></div>
            <p class="font-label-md mt-2 text-on-surface-variant">Belum ada pengajuan cuti.</p>
        </div>
        <script src="/assets/lottie/vendor/lottie-web.min.js"></script>
        <script src="/assets/lottie/lottie-helper.js"></script>
        <script>
            mountLottie('leave-empty-anim', '/assets/lottie/empty-state.json', { loop: true, autoplay: true });
        </script>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($requests->hasPages())
    <div class="pt-unit-md">{{ $requests->appends(['status' => $status])->links() }}</div>
    @endif

</main>

<!-- FAB -->
<button class="fixed bottom-24 bg-primary-container text-on-primary shadow-lg rounded-full w-14 h-14 flex items-center justify-center hover:bg-primary transition-colors z-40"
        style="left: calc(50% + 195px - 72px);"
        onclick="window.location.href='/leave/request'">
    <span class="material-symbols-outlined">add</span>
</button>

<!-- BottomNavBar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center px-2 py-3 bg-surface border-t border-border backdrop-blur-md shadow-lg mx-auto">
    <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high active:scale-90 transition-all duration-200" href="/employee/dashboard">
        <span class="material-symbols-outlined mb-1">home</span>
        <span class="font-label-sm text-label-sm">Beranda</span>
    </a>
    <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high active:scale-90 transition-all duration-200" href="/attendance/checkin">
        <span class="material-symbols-outlined mb-1">schedule</span>
        <span class="font-label-sm text-label-sm">Presensi</span>
    </a>
    <a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1 active:scale-90 transition-all duration-200" href="/leave/history">
        <span class="material-symbols-outlined mb-1" style="font-variation-settings: 'FILL' 1;">event_note</span>
        <span class="font-label-sm text-label-sm">Cuti</span>
    </a>
    <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high active:scale-90 transition-all duration-200" href="/payslip/detail">
        <span class="material-symbols-outlined mb-1">payments</span>
        <span class="font-label-sm text-label-sm">Slip Gaji</span>
    </a>
    <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high active:scale-90 transition-all duration-200" href="{{ route('my.profile') }}">
        <span class="material-symbols-outlined mb-1">person</span>
        <span class="font-label-sm text-label-sm">Profil</span>
    </a>
</nav>
</body></html>
