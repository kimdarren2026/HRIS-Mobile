<!DOCTYPE html><html class="light" lang="en"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Attendance Check-In - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                    "secondary-fixed-dim": "#c3c0ff",
                    "on-secondary": "#ffffff",
                    "outline-variant": "#c7c4d8",
                    "surface-tint": "#4d44e3",
                    "on-tertiary-container": "#ffd2be",
                    "on-primary-fixed": "#0f0069",
                    "outline": "#777587",
                    "surface-container-low": "#f0f3ff",
                    "inverse-on-surface": "#ebf1ff",
                    "surface-container-highest": "#dce2f3",
                    "secondary-fixed": "#e3dfff",
                    "error-container": "#ffdad6",
                    "surface-bright": "#f9f9ff",
                    "primary-fixed": "#e2dfff",
                    "on-background": "#151c27",
                    "tertiary-container": "#a44100",
                    "secondary-container": "#6860ef",
                    "on-tertiary-fixed": "#351000",
                    "surface-dim": "#d3daea",
                    "primary": "#3525cd",
                    "on-tertiary": "#ffffff",
                    "on-secondary-fixed": "#100069",
                    "surface-container": "#e7eefe",
                    "tertiary-fixed-dim": "#ffb695",
                    "secondary": "#4e45d5",
                    "background": "#f9f9ff",
                    "surface-container-high": "#e2e8f8",
                    "on-primary-container": "#dad7ff",
                    "tertiary-fixed": "#ffdbcc",
                    "on-surface-variant": "#464555",
                    "on-surface": "#151c27",
                    "error": "#ba1a1a",
                    "surface-container-lowest": "#ffffff",
                    "on-primary": "#ffffff",
                    "danger": "#EF4444",
                    "on-secondary-fixed-variant": "#372abf",
                    "surface-variant": "#dce2f3",
                    "surface": "#F9FAFB",
                    "border": "#E5E7EB",
                    "inverse-primary": "#c3c0ff",
                    "tertiary": "#7e3000",
                    "on-primary-fixed-variant": "#3323cc",
                    "on-secondary-container": "#fffbff",
                    "primary-container": "#4f46e5",
                    "success": "#10B981",
                    "on-error-container": "#93000a",
                    "warning": "#F59E0B",
                    "on-tertiary-fixed-variant": "#7b2f00",
                    "on-error": "#ffffff",
                    "inverse-surface": "#2a313d",
                    "primary-fixed-dim": "#c3c0ff"
            },
            "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
            },
            "spacing": {
                    "card-gap": "12px",
                    "container-margin": "16px",
                    "unit-lg": "24px",
                    "unit-xs": "4px",
                    "unit-md": "16px",
                    "unit-xl": "32px",
                    "unit-sm": "8px"
            },
            "fontFamily": {
                    "body-lg": [
                            "Inter"
                    ],
                    "status-badge": [
                            "Inter"
                    ],
                    "headline-md": [
                            "Inter"
                    ],
                    "headline-lg": [
                            "Inter"
                    ],
                    "label-sm": [
                            "Inter"
                    ],
                    "label-md": [
                            "Inter"
                    ],
                    "body-md": [
                            "Inter"
                    ]
            },
            "fontSize": {
                    "body-lg": [
                            "16px",
                            {
                                    "lineHeight": "24px",
                                    "fontWeight": "400"
                            }
                    ],
                    "status-badge": [
                            "12px",
                            {
                                    "lineHeight": "12px",
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
                    "headline-lg": [
                            "24px",
                            {
                                    "lineHeight": "32px",
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
                    "label-md": [
                            "12px",
                            {
                                    "lineHeight": "16px",
                                    "letterSpacing": "0.05em",
                                    "fontWeight": "600"
                            }
                    ],
                    "body-md": [
                            "14px",
                            {
                                    "lineHeight": "20px",
                                    "fontWeight": "400"
                            }
                    ]
            }
        },
        },
      }
    </script>
<style>
        body {
            -webkit-tap-highlight-color: transparent;
        }
        /* Pulse animation for map pin */
        @keyframes pin-pulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }
        .animate-pin-pulse {
            animation: pin-pulse 2s infinite ease-in-out;
        }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
</head>
<body class="bg-background text-on-surface font-body-md min-h-screen flex flex-col antialiased w-full max-w-[390px] mx-auto overflow-x-hidden pb-24">

@if($alreadyCheckedIn && $alreadyCheckedOut)
<!-- Done for today: checked in AND checked out -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm">
<a href="/employee/dashboard" class="p-2 -ml-2 rounded-full hover:bg-surface-container active:scale-95 transition-all text-on-surface-variant"><span class="material-symbols-outlined">arrow_back</span></a>
<h1 class="font-headline-md text-headline-md text-primary ml-2 flex-1">Attendance</h1>
</header>
<main class="flex-1 mt-16 px-container-margin py-unit-md flex flex-col items-center justify-center gap-unit-lg">
<div class="flex flex-col items-center gap-4 py-12">
<span class="material-symbols-outlined text-success text-[64px]" style="font-variation-settings:'FILL' 1;">task_alt</span>
<h2 class="font-headline-md text-headline-md text-on-surface">Presensi Selesai</h2>
<p class="font-body-md text-body-md text-on-surface-variant text-center">Anda sudah melakukan check-in dan check-out hari ini.</p>
<a href="/attendance/history" class="mt-4 bg-primary text-on-primary font-label-md text-label-md px-6 py-3 rounded-xl">Lihat Riwayat</a>
</div>
</main>

@elseif($alreadyCheckedIn)
<!-- Checked in but not yet checked out — show checkout form -->
@if($errors->has('general'))
<div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-[60] px-container-margin pt-2">
<div class="bg-error-container text-on-error-container rounded-lg px-4 py-3 font-body-md text-body-md flex items-center gap-2">
<span class="material-symbols-outlined text-[18px] shrink-0">error</span>
<span>{{ $errors->first('general') }}</span>
</div>
</div>
@endif
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm">
<a href="/employee/dashboard" class="p-2 -ml-2 rounded-full hover:bg-surface-container active:scale-95 transition-all text-on-surface-variant"><span class="material-symbols-outlined" style="font-variation-settings:'FILL' 0;">arrow_back</span></a>
<h1 class="font-headline-md text-headline-md text-primary ml-2 flex-1">Check Out</h1>
</header>
<form id="checkout-form" method="POST" action="/attendance/check-out">
@csrf
<input type="hidden" id="co-lat" name="lat">
<input type="hidden" id="co-lng" name="lng">
<main class="flex-1 mt-16 px-container-margin py-unit-md flex flex-col gap-unit-lg">
<!-- Clock -->
<section class="flex flex-col items-center justify-center pt-unit-sm">
<p class="font-body-md text-body-md text-on-surface-variant mb-1" id="current-date">—</p>
<div class="flex items-baseline gap-1">
<span class="font-headline-lg text-4xl font-bold tracking-tight text-on-surface" id="current-time">--:--</span>
<span class="font-label-md text-label-md text-on-surface-variant font-bold" id="ampm">--</span>
</div>
</section>
<!-- Today's check-in summary -->
<section class="bg-surface rounded-xl border border-border shadow-sm p-4">
<div class="flex items-center justify-between mb-3">
<h2 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Check-in Today</h2>
@php
$coStatusCls  = $todayRecord->status === 'APPROVED' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning';
$coStatusIcon = $todayRecord->status === 'APPROVED' ? 'check_circle' : 'pending';
$coStatusLbl  = $todayRecord->status === 'APPROVED' ? 'Approved' : 'Pending Review';
@endphp
<span class="px-2 py-1 rounded-full {{ $coStatusCls }} text-status-badge font-status-badge flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">{{ $coStatusIcon }}</span> {{ $coStatusLbl }}
</span>
</div>
<p class="font-headline-md text-headline-md text-on-background">{{ $todayRecord->check_in_time->format('h:i A') }}</p>
@if($todayRecord->status === 'PENDING_REVIEW')
<p class="font-body-md text-body-md text-on-surface-variant mt-2 flex items-start gap-1.5">
<span class="material-symbols-outlined text-warning text-[16px] mt-0.5 shrink-0">info</span>
Check-out akan dicatat. Status Pending Review tetap menunggu keputusan HR.
</p>
@endif
</section>
<!-- GPS Location -->
<section class="bg-surface rounded-xl border border-border shadow-sm overflow-hidden flex flex-col">
<div class="p-4 border-b border-border flex justify-between items-center bg-surface-container-low">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-xl">location_on</span>
<h2 class="font-label-md text-label-md text-on-surface">Your Location</h2>
</div>
<div id="co-radius-badge" class="flex items-center gap-1.5 px-2 py-1 rounded-full bg-surface-container border border-outline-variant/50">
<div class="w-1.5 h-1.5 rounded-full bg-outline animate-pulse"></div>
<span class="font-status-badge text-status-badge text-on-surface-variant">Detecting GPS...</span>
</div>
</div>
<div class="relative w-full h-32 bg-surface-container overflow-hidden flex items-center justify-center">
<div id="co-gps-loading" class="flex flex-col items-center justify-center gap-2 text-on-surface-variant">
<div class="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
<span class="font-label-sm text-label-sm">Mengambil lokasi...</span>
</div>
<div id="co-gps-error-msg" class="hidden flex-col items-center gap-2 text-center px-4">
<span class="material-symbols-outlined text-error text-[32px]">location_off</span>
<p class="font-label-sm text-label-sm text-error" id="co-gps-error-text">Izin lokasi ditolak. Aktifkan GPS di pengaturan browser.</p>
<button type="button" onclick="retryCoGps()" class="mt-1 text-primary font-label-sm text-label-sm underline">Coba lagi</button>
</div>
<div id="co-gps-ok" class="hidden items-center justify-center w-full h-full">
<div class="relative flex items-center justify-center">
<div class="absolute w-12 h-12 rounded-full bg-primary/20 animate-pin-pulse"></div>
<div class="absolute w-4 h-4 rounded-full bg-primary shadow-sm border-2 border-white z-10"></div>
<span class="material-symbols-outlined absolute -top-8 text-primary drop-shadow-md" style="font-variation-settings:'FILL' 1; font-size:32px;">location_on</span>
</div>
</div>
</div>
<div class="p-3 bg-surface text-center">
<p class="font-label-sm text-label-sm text-on-surface-variant flex items-center justify-center gap-1">
<span class="material-symbols-outlined text-[14px]">my_location</span>
<span id="co-gps-detail">Waiting for location...</span>
</p>
</div>
</section>
</main>
</form>
<!-- Fixed checkout submit button -->
<div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] px-container-margin pb-unit-md pt-unit-sm bg-surface/90 backdrop-blur-md border-t border-border z-40">
<button id="co-submit-btn" type="submit" form="checkout-form" disabled
    class="w-full bg-primary hover:bg-primary/90 disabled:opacity-40 disabled:cursor-not-allowed text-on-primary font-headline-md text-body-lg font-semibold py-3.5 rounded-xl shadow-sm active:scale-[0.98] transition-all flex items-center justify-center gap-2">
<span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">logout</span>
<span>Confirm Check Out</span>
</button>
</div>

@else

{{-- Session error --}}
@if($errors->has('general'))
<div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-[60] px-container-margin pt-2">
<div class="bg-error-container text-on-error-container rounded-lg px-4 py-3 font-body-md text-body-md flex items-center gap-2">
<span class="material-symbols-outlined text-[18px] shrink-0">error</span>
<span>{{ $errors->first('general') }}</span>
</div>
</div>
@endif

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm">
<a href="/employee/dashboard" class="p-2 -ml-2 rounded-full hover:bg-surface-container active:scale-95 transition-all text-on-surface-variant"><span class="material-symbols-outlined" style="font-variation-settings:'FILL' 0;">arrow_back</span></a>
<h1 class="font-headline-md text-headline-md text-primary ml-2 flex-1">Check In</h1>
</header>

<form id="checkin-form" method="POST" action="/attendance/check-in" enctype="multipart/form-data">
@csrf
<input type="hidden" id="lat" name="lat">
<input type="hidden" id="lng" name="lng">

<main class="flex-1 mt-16 px-container-margin py-unit-md flex flex-col gap-unit-lg">

@if(!$officeLocation)
<div class="bg-warning/10 border border-warning/30 rounded-lg px-4 py-3 flex items-start gap-2">
<span class="material-symbols-outlined text-warning text-[18px] shrink-0 mt-0.5">warning</span>
<p class="font-body-md text-body-md text-on-surface-variant">Lokasi kantor belum dikonfigurasi. Check-in tetap bisa dilakukan namun akan masuk status <strong>Pending Review</strong>. Hubungi HR untuk mengatur lokasi kantor.</p>
</div>
@endif

<!-- Time Context -->
<section class="flex flex-col items-center justify-center pt-unit-sm">
<p class="font-body-md text-body-md text-on-surface-variant mb-1" id="current-date">—</p>
<div class="flex items-baseline gap-1">
<span class="font-headline-lg text-4xl font-bold tracking-tight text-on-surface" id="current-time">--:--</span>
<span class="font-label-md text-label-md text-on-surface-variant font-bold" id="ampm">--</span>
</div>
</section>

<!-- Location Card -->
<section class="bg-surface rounded-xl border border-border shadow-sm overflow-hidden flex flex-col">
<div class="p-4 border-b border-border flex justify-between items-center bg-surface-container-low">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-primary text-xl">location_on</span>
<h2 class="font-label-md text-label-md text-on-surface">Your Location</h2>
</div>
<div id="radius-badge" class="flex items-center gap-1.5 px-2 py-1 rounded-full bg-surface-container border border-outline-variant/50">
<div class="w-1.5 h-1.5 rounded-full bg-outline animate-pulse"></div>
<span class="font-status-badge text-status-badge text-on-surface-variant">Detecting GPS...</span>
</div>
</div>
<div class="relative w-full h-32 bg-surface-container overflow-hidden flex items-center justify-center">
<div id="gps-loading" class="flex flex-col items-center justify-center gap-2 text-on-surface-variant">
<div class="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
<span class="font-label-sm text-label-sm">Mengambil lokasi...</span>
</div>
<div id="gps-error-msg" class="hidden flex-col items-center gap-2 text-center px-4">
<span class="material-symbols-outlined text-error text-[32px]">location_off</span>
<p class="font-label-sm text-label-sm text-error" id="gps-error-text">Izin lokasi ditolak. Aktifkan GPS di pengaturan browser.</p>
<button type="button" onclick="retryGps()" class="mt-1 text-primary font-label-sm text-label-sm underline">Coba lagi</button>
</div>
<div id="gps-ok" class="hidden items-center justify-center w-full h-full">
<div class="relative flex items-center justify-center">
<div class="absolute w-12 h-12 rounded-full bg-primary/20 animate-pin-pulse"></div>
<div class="absolute w-4 h-4 rounded-full bg-primary shadow-sm border-2 border-white z-10"></div>
<span class="material-symbols-outlined absolute -top-8 text-primary drop-shadow-md" style="font-variation-settings:'FILL' 1; font-size:32px;">location_on</span>
</div>
</div>
</div>
<div class="p-3 bg-surface text-center">
<p class="font-label-sm text-label-sm text-on-surface-variant flex items-center justify-center gap-1">
<span class="material-symbols-outlined text-[14px]">my_location</span>
<span id="gps-detail">Waiting for location...</span>
</p>
<p class="font-label-sm text-label-sm text-on-surface-variant mt-2 opacity-80 italic">Reason required only when outside office radius.</p>
</div>
</section>

<!-- Selfie Capture Section -->
<section class="flex flex-col gap-unit-xs">
<label class="font-label-md text-label-md text-on-surface px-1">Photo Verification</label>
@error('photo')
<p class="text-error font-label-sm text-label-sm px-1">{{ $message }}</p>
@enderror
<div id="camera-error" class="hidden bg-error-container text-on-error-container rounded-lg px-4 py-3 font-label-sm text-label-sm items-center gap-2">
<span class="material-symbols-outlined text-[16px] shrink-0">videocam_off</span>
<span id="camera-error-text">Kamera tidak tersedia.</span>
</div>
<div id="camera-area" class="relative w-full h-48 bg-surface-container-high rounded-xl border-2 border-dashed border-outline-variant overflow-hidden">
<div id="camera-loading" class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-on-surface-variant z-10">
<div class="w-14 h-14 rounded-full bg-surface shadow-sm flex items-center justify-center">
<span class="material-symbols-outlined text-3xl" style="font-variation-settings:'FILL' 0;">photo_camera</span>
</div>
<span class="font-label-sm text-label-sm font-medium">Memulai kamera...</span>
</div>
<video id="selfie-video" autoplay playsinline muted class="hidden absolute inset-0 w-full h-full object-cover z-10"></video>
<div id="capture-overlay" class="hidden absolute inset-0 flex items-end justify-center pb-3 z-20">
<button type="button" id="capture-btn" class="bg-primary text-on-primary rounded-full px-5 py-2 font-label-md text-label-md flex items-center gap-1.5 shadow-md active:scale-95 transition-all">
<span class="material-symbols-outlined text-[18px]" style="font-variation-settings:'FILL' 1;">photo_camera</span>
Capture Selfie
</button>
</div>
<img id="photo-preview" class="hidden absolute inset-0 w-full h-full object-cover z-10" alt="Selfie preview">
<button type="button" id="photo-retake" class="hidden absolute bottom-2 right-2 z-20 bg-surface/80 rounded-full px-3 py-1 font-label-sm text-label-sm text-primary backdrop-blur-sm">Retake</button>
</div>
<canvas id="selfie-canvas" class="hidden"></canvas>
</section>

<!-- Reason (hidden until outside radius detected) -->
<section id="reason-section" class="flex flex-col gap-unit-xs hidden">
<label class="font-label-md text-label-md text-on-surface px-1 flex justify-between" for="reason">
Alasan absen di luar radius <span class="text-danger">*</span>
</label>
@error('reason')
<p class="text-error font-label-sm text-label-sm px-1">{{ $message }}</p>
@enderror
<textarea id="reason" name="reason" rows="3"
    class="w-full rounded-lg border border-outline-variant bg-surface px-4 py-3 font-body-md text-body-md text-on-surface focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-shadow resize-none placeholder:text-outline"
    placeholder="Contoh: Rapat klien, bekerja dari rumah... (min. 10 karakter)"
    maxlength="500">{{ old('reason') }}</textarea>
<p class="font-label-sm text-label-sm text-warning flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">pending</span> Status: Menunggu Review HR
</p>
</section>

</main>
</form>

<!-- Fixed submit button (outside form but linked via form="checkin-form") -->
<div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] px-container-margin pb-unit-md pt-unit-sm bg-surface/90 backdrop-blur-md border-t border-border z-40">
<button id="submit-btn" type="submit" form="checkin-form" disabled
    class="w-full bg-primary hover:bg-primary/90 disabled:opacity-40 disabled:cursor-not-allowed text-on-primary font-headline-md text-body-lg font-semibold py-3.5 rounded-xl shadow-sm active:scale-[0.98] transition-all flex items-center justify-center gap-2">
<span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">login</span>
<span id="submit-label">Confirm Check In</span>
</button>
</div>

@endif

<script>
(function() {
    // Office coords from server
    const OFFICE_LAT    = @json($officeLocation ? (float) $officeLocation->latitude   : null);
    const OFFICE_LNG    = @json($officeLocation ? (float) $officeLocation->longitude  : null);
    const OFFICE_RADIUS = @json($officeLocation ? (int)   $officeLocation->radius_meters : 100);

    let gpsReady   = false;
    let photoReady = false;

    // ── Clock ──────────────────────────────────────────────────────────────
    function updateClock() {
        const now  = new Date();
        let h      = now.getHours(), m = now.getMinutes();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        const el = document.getElementById('current-time');
        if (el) el.innerText = h + ':' + (m < 10 ? '0' + m : m);
        const ap = document.getElementById('ampm');
        if (ap) ap.innerText = ampm;
        const de = document.getElementById('current-date');
        if (de) de.innerText = now.toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'short', year:'numeric' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ── Haversine distance ─────────────────────────────────────────────────
    function haversine(lat1, lng1, lat2, lng2) {
        const R = 6371000, toR = Math.PI / 180;
        const dLat = (lat2 - lat1) * toR, dLng = (lng2 - lng1) * toR;
        const a = Math.sin(dLat/2)**2 + Math.cos(lat1*toR)*Math.cos(lat2*toR)*Math.sin(dLng/2)**2;
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    // ── Submit guard ───────────────────────────────────────────────────────
    function updateSubmit() {
        const btn = document.getElementById('submit-btn');
        if (!btn) return;
        btn.disabled = !(gpsReady && photoReady);
    }

    // ── GPS ────────────────────────────────────────────────────────────────
    function startGps() {
        if (!navigator.geolocation) {
            showGpsError('Perangkat/browser ini tidak mendukung GPS. Gunakan browser yang mendukung geolocation.');
            return;
        }
        const loadEl = document.getElementById('gps-loading');
        const okEl   = document.getElementById('gps-ok');
        const errDiv = document.getElementById('gps-error-msg');
        if (errDiv) { errDiv.classList.add('hidden'); errDiv.classList.remove('flex'); }
        if (okEl)   { okEl.classList.add('hidden'); okEl.classList.remove('flex'); }
        if (loadEl) loadEl.classList.remove('hidden');

        navigator.geolocation.getCurrentPosition(
            function(pos) {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const acc = Math.round(pos.coords.accuracy);

                document.getElementById('lat').value = lat;
                document.getElementById('lng').value = lng;

                if (loadEl) loadEl.classList.add('hidden');
                if (okEl)   { okEl.classList.remove('hidden'); okEl.classList.add('flex'); }

                gpsReady = true;
                updateSubmit();

                // Check radius to show/hide reason field and update badge
                let withinRadius = true;
                if (OFFICE_LAT !== null) {
                    const dist = haversine(lat, lng, OFFICE_LAT, OFFICE_LNG);
                    withinRadius = dist <= OFFICE_RADIUS;

                    const badge   = document.getElementById('radius-badge');
                    const detail  = document.getElementById('gps-detail');
                    const reason  = document.getElementById('reason-section');
                    const label   = document.getElementById('submit-label');

                    detail.innerText = 'Akurasi: ' + acc + 'm';

                    if (withinRadius) {
                        badge.className   = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-success/10 border border-success/20';
                        badge.innerHTML   = '<div class="w-1.5 h-1.5 rounded-full bg-success animate-pulse"></div><span class="font-status-badge text-status-badge text-success">Dalam radius kantor</span>';
                        if (reason) reason.classList.add('hidden');
                        if (label)  label.innerText = 'Konfirmasi Check In';
                    } else {
                        badge.className   = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-error-container border border-error/20';
                        badge.innerHTML   = '<div class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></div><span class="font-status-badge text-status-badge text-error">Di luar radius kantor</span>';
                        const distM       = Math.round(haversine(lat, lng, OFFICE_LAT, OFFICE_LNG));
                        detail.innerText  = 'Akurasi: ' + acc + 'm • ' + distM + 'm dari kantor';
                        if (reason) reason.classList.remove('hidden');
                        if (label)  label.innerText = 'Kirim untuk Review HR';
                    }
                } else {
                    const detail = document.getElementById('gps-detail');
                    if (detail) detail.innerText = 'Akurasi: ' + acc + 'm — lokasi kantor belum diatur';
                }
            },
            function(err) {
                const msgs = {
                    1: 'Izin lokasi ditolak. Aktifkan akses lokasi di pengaturan browser, lalu tekan "Coba lagi".',
                    2: 'Posisi tidak tersedia. Pastikan GPS perangkat aktif.',
                    3: 'Timeout GPS. Periksa koneksi dan tekan "Coba lagi".',
                };
                showGpsError(msgs[err.code] || 'Gagal mendapatkan lokasi.');
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    }

    window.retryGps = function() { startGps(); };

    function showGpsError(msg) {
        const loadEl = document.getElementById('gps-loading');
        const errDiv = document.getElementById('gps-error-msg');
        const okDiv  = document.getElementById('gps-ok');
        const errTxt = document.getElementById('gps-error-text');
        if (loadEl) loadEl.classList.add('hidden');
        if (errDiv) { errDiv.classList.remove('hidden'); errDiv.classList.add('flex'); }
        if (okDiv)  { okDiv.classList.add('hidden'); okDiv.classList.remove('flex'); }
        if (errTxt) errTxt.innerText = msg;
        const badge = document.getElementById('radius-badge');
        if (badge) {
            badge.className = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-error-container border border-error/20';
            badge.innerHTML = '<span class="material-symbols-outlined text-error text-[14px]">location_off</span><span class="font-status-badge text-status-badge text-error">GPS tidak tersedia</span>';
        }
    }

    // ── Camera / Selfie (getUserMedia) ────────────────────────────────────
    let cameraStream = null;
    let capturedBlob = null;

    function showCameraError(msg) {
        const errDiv = document.getElementById('camera-error');
        const errTxt = document.getElementById('camera-error-text');
        const area   = document.getElementById('camera-area');
        if (errTxt) errTxt.innerText = msg;
        if (errDiv) { errDiv.classList.remove('hidden'); errDiv.classList.add('flex'); }
        if (area)   area.classList.add('hidden');
    }

    function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showCameraError('Browser ini tidak mendukung akses kamera langsung.');
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
            .then(function(stream) {
                cameraStream = stream;
                const video   = document.getElementById('selfie-video');
                const loading = document.getElementById('camera-loading');
                const overlay = document.getElementById('capture-overlay');
                if (!video) return;
                video.srcObject = stream;
                video.onloadedmetadata = function() {
                    if (loading) loading.classList.add('hidden');
                    video.classList.remove('hidden');
                    if (overlay) overlay.classList.remove('hidden');
                };
            })
            .catch(function(err) {
                let msg = 'Kamera tidak tersedia.';
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    msg = 'Izin kamera ditolak. Aktifkan izin kamera di pengaturan browser, lalu muat ulang halaman.';
                } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                    msg = 'Tidak ada kamera yang terdeteksi pada perangkat ini.';
                } else if (err.name === 'NotReadableError') {
                    msg = 'Kamera sedang digunakan oleh aplikasi lain.';
                }
                showCameraError(msg);
            });
    }

    const captureBtn = document.getElementById('capture-btn');
    if (captureBtn) {
        captureBtn.addEventListener('click', function() {
            const video   = document.getElementById('selfie-video');
            const canvas  = document.getElementById('selfie-canvas');
            const preview = document.getElementById('photo-preview');
            const retake  = document.getElementById('photo-retake');
            const overlay = document.getElementById('capture-overlay');
            if (!video || !canvas || video.videoWidth === 0) return;

            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            if (preview) { preview.src = dataUrl; preview.classList.remove('hidden'); }
            if (overlay) overlay.classList.add('hidden');
            video.classList.add('hidden');
            if (retake)  retake.classList.remove('hidden');

            if (cameraStream) {
                cameraStream.getTracks().forEach(function(t) { t.stop(); });
                cameraStream = null;
            }

            canvas.toBlob(function(blob) {
                capturedBlob = blob;
                photoReady   = true;
                updateSubmit();
            }, 'image/jpeg', 0.9);
        });
    }

    const retakeBtn = document.getElementById('photo-retake');
    if (retakeBtn) {
        retakeBtn.addEventListener('click', function() {
            const preview = document.getElementById('photo-preview');
            if (preview) { preview.src = ''; preview.classList.add('hidden'); }
            retakeBtn.classList.add('hidden');
            capturedBlob = null;
            photoReady   = false;
            updateSubmit();
            startCamera();
        });
    }

    // Intercept form submit — send captured blob via fetch
    const checkinForm = document.getElementById('checkin-form');
    if (checkinForm) {
        checkinForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!capturedBlob) return;

            const btn = document.getElementById('submit-btn');
            const lbl = document.getElementById('submit-label');
            if (btn) btn.disabled = true;
            if (lbl) lbl.innerText = 'Submitting...';

            const csrf     = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const formData = new FormData(checkinForm);
            formData.set('photo', capturedBlob, 'selfie.jpg');

            fetch('/attendance/check-in', {
                method:   'POST',
                headers:  { 'X-CSRF-TOKEN': csrf },
                body:     formData,
                redirect: 'follow',
            })
            .then(function(r) {
                if (r.url && r.url.includes('/attendance/history')) {
                    window.location.replace(r.url);
                } else {
                    return r.text().then(function(html) {
                        document.open();
                        document.write(html);
                        document.close();
                    });
                }
            })
            .catch(function() {
                if (btn) btn.disabled = false;
                if (lbl) lbl.innerText = 'Confirm Check In';
            });
        });
    }

    // If there are old validation errors, show reason field
    @if($errors->has('reason') || old('reason'))
    const rs = document.getElementById('reason-section');
    if (rs) rs.classList.remove('hidden');
    @endif

    // Start GPS and camera only when the check-in form is present
    if (document.getElementById('checkin-form')) {
        startGps();
        startCamera();
    }

    // ── Check-out GPS ──────────────────────────────────────────────────────
    if (document.getElementById('checkout-form')) {
        let coGpsReady = false;

        function updateCoSubmit() {
            const btn = document.getElementById('co-submit-btn');
            if (!btn) return;
            btn.disabled = !coGpsReady;
        }

        function startCoGps() {
            if (!navigator.geolocation) {
                showCoGpsError('Perangkat/browser ini tidak mendukung GPS.');
                return;
            }
            const loadEl = document.getElementById('co-gps-loading');
            const okEl   = document.getElementById('co-gps-ok');
            const errDiv = document.getElementById('co-gps-error-msg');
            if (errDiv) { errDiv.classList.add('hidden'); errDiv.classList.remove('flex'); }
            if (okEl)   { okEl.classList.add('hidden'); okEl.classList.remove('flex'); }
            if (loadEl) loadEl.classList.remove('hidden');

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const acc = Math.round(pos.coords.accuracy);

                    document.getElementById('co-lat').value = lat;
                    document.getElementById('co-lng').value = lng;

                    if (loadEl) loadEl.classList.add('hidden');
                    if (okEl)   { okEl.classList.remove('hidden'); okEl.classList.add('flex'); }

                    coGpsReady = true;
                    updateCoSubmit();

                    const badge  = document.getElementById('co-radius-badge');
                    const detail = document.getElementById('co-gps-detail');

                    if (OFFICE_LAT !== null) {
                        const dist        = haversine(lat, lng, OFFICE_LAT, OFFICE_LNG);
                        const withinRadius = dist <= OFFICE_RADIUS;
                        const distM       = Math.round(dist);

                        if (withinRadius) {
                            badge.className = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-success/10 border border-success/20';
                            badge.innerHTML = '<div class="w-1.5 h-1.5 rounded-full bg-success animate-pulse"></div><span class="font-status-badge text-status-badge text-success">Dalam radius kantor</span>';
                        } else {
                            badge.className = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-warning/10 border border-warning/20';
                            badge.innerHTML = '<div class="w-1.5 h-1.5 rounded-full bg-warning animate-pulse"></div><span class="font-status-badge text-status-badge text-warning">Di luar radius</span>';
                        }
                        if (detail) detail.innerText = 'Akurasi: ' + acc + 'm' + (withinRadius ? '' : ' • ' + distM + 'm dari kantor');
                    } else {
                        if (detail) detail.innerText = 'Akurasi: ' + acc + 'm';
                    }
                },
                function(err) {
                    const msgs = {
                        1: 'Izin lokasi ditolak. Aktifkan akses lokasi di pengaturan browser.',
                        2: 'Posisi tidak tersedia. Pastikan GPS perangkat aktif.',
                        3: 'Timeout GPS. Periksa koneksi dan tekan "Coba lagi".',
                    };
                    showCoGpsError(msgs[err.code] || 'Gagal mendapatkan lokasi.');
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
            );
        }

        window.retryCoGps = function() { startCoGps(); };

        function showCoGpsError(msg) {
            const loadEl = document.getElementById('co-gps-loading');
            const errDiv = document.getElementById('co-gps-error-msg');
            const okDiv  = document.getElementById('co-gps-ok');
            const errTxt = document.getElementById('co-gps-error-text');
            if (loadEl) loadEl.classList.add('hidden');
            if (errDiv) { errDiv.classList.remove('hidden'); errDiv.classList.add('flex'); }
            if (okDiv)  { okDiv.classList.add('hidden'); okDiv.classList.remove('flex'); }
            if (errTxt) errTxt.innerText = msg;
            const badge = document.getElementById('co-radius-badge');
            if (badge) {
                badge.className = 'flex items-center gap-1.5 px-2 py-1 rounded-full bg-error-container border border-error/20';
                badge.innerHTML = '<span class="material-symbols-outlined text-error text-[14px]">location_off</span><span class="font-status-badge text-status-badge text-error">GPS tidak tersedia</span>';
            }
        }

        startCoGps();
    }
})();
</script>
</body></html>
