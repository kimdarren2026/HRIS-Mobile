<!DOCTYPE html><html lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Absen Masuk - HRIS Mobile App</title>
<!-- Google Fonts & Material Symbols -->
<link href="https://fonts.googleapis.com" rel="preconnect">
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<style>
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
        }
    </style>
<!-- Tailwind CSS & Configuration -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-error": "#ffffff",
                        "on-secondary-fixed": "#100069",
                        "surface-container-highest": "#dce2f3",
                        "primary-fixed": "#e2dfff",
                        "on-primary-container": "#dad7ff",
                        "surface-container": "#e7eefe",
                        "on-background": "#151c27",
                        "on-secondary-fixed-variant": "#372abf",
                        "on-surface-variant": "#464555",
                        "surface-tint": "#4d44e3",
                        "background": "#f9f9ff",
                        "inverse-primary": "#c3c0ff",
                        "on-secondary-container": "#fffbff",
                        "inverse-surface": "#2a313d",
                        "on-tertiary-fixed-variant": "#7b2f00",
                        "on-primary": "#ffffff",
                        "surface-container-high": "#e2e8f8",
                        "success": "#10B981",
                        "surface-container-lowest": "#ffffff",
                        "surface-dim": "#d3daea",
                        "on-secondary": "#ffffff",
                        "tertiary-fixed": "#ffdbcc",
                        "on-tertiary-container": "#ffd2be",
                        "secondary-fixed": "#e3dfff",
                        "secondary": "#4e45d5",
                        "tertiary-fixed-dim": "#ffb695",
                        "error": "#ba1a1a",
                        "primary-fixed-dim": "#c3c0ff",
                        "warning": "#F59E0B",
                        "on-error-container": "#93000a",
                        "outline-variant": "#c7c4d8",
                        "tertiary": "#7e3000",
                        "outline": "#777587",
                        "on-tertiary-fixed": "#351000",
                        "surface": "#F9FAFB",
                        "surface-bright": "#f9f9ff",
                        "danger": "#EF4444",
                        "secondary-fixed-dim": "#c3c0ff",
                        "on-primary-fixed-variant": "#3323cc",
                        "primary": "#3525cd",
                        "on-primary-fixed": "#0f0069",
                        "secondary-container": "#6860ef",
                        "border": "#E5E7EB",
                        "surface-variant": "#dce2f3",
                        "tertiary-container": "#a44100",
                        "surface-container-low": "#f0f3ff",
                        "error-container": "#ffdad6",
                        "primary-container": "#4f46e5",
                        "inverse-on-surface": "#ebf1ff",
                        "on-tertiary": "#ffffff",
                        "on-surface": "#151c27"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "container-margin": "16px",
                        "unit-xl": "32px",
                        "unit-xs": "4px",
                        "unit-sm": "8px",
                        "unit-md": "16px",
                        "unit-lg": "24px",
                        "card-gap": "12px"
                    },
                    "fontFamily": {
                        "headline-lg": ["Inter"],
                        "headline-md": ["Inter"],
                        "body-lg": ["Inter"],
                        "body-md": ["Inter"],
                        "label-md": ["Inter"],
                        "status-badge": ["Inter"],
                        "label-sm": ["Inter"]
                    },
                    "fontSize": {
                        "headline-lg": ["24px", { "lineHeight": "32px", "fontWeight": "700" }],
                        "headline-md": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                        "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                        "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                        "label-md": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "status-badge": ["12px", { "lineHeight": "12px", "fontWeight": "700" }],
                        "label-sm": ["11px", { "lineHeight": "14px", "fontWeight": "500" }]
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#e5e7eb] flex justify-center items-start min-h-screen font-body-md text-on-background">
<!-- Mobile Device Container -->
<div class="w-full max-w-[390px] h-auto min-h-screen bg-background relative overflow-hidden flex flex-col shadow-2xl pb-20">
<!-- Header -->
<header class="flex items-center px-container-margin h-16 bg-surface border-b border-border sticky top-0 z-40 shrink-0">
<button class="p-2 -ml-2 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors" onclick="window.location.href='/employee/dashboard'">
<span class="material-symbols-outlined" data-icon="arrow_back">arrow_back</span>
</button>
<h1 class="flex-1 text-center font-headline-md text-headline-md text-on-surface mr-8">Absen Masuk</h1>
</header>
<!-- Scrollable Content -->
<form id="checkin-outside-form" method="POST" action="/attendance/check-in" enctype="multipart/form-data">
@csrf
<input type="hidden" id="lat-out" name="lat">
<input type="hidden" id="lng-out" name="lng">

<main class="flex-1 overflow-y-auto pb-6">
<!-- Top Section: Date & Time -->
<div class="py-unit-lg flex flex-col items-center">
<div class="font-headline-lg text-[32px] leading-tight font-bold text-on-surface" id="time-out">--:-- --</div>
<div class="font-body-md text-body-md text-on-surface-variant mt-1" id="date-out">—</div>
</div>
<!-- Location Card -->
<div class="mx-container-margin bg-surface border border-border rounded-xl shadow-sm p-unit-md mb-unit-lg">
<div class="flex justify-between items-start mb-unit-sm">
<div>
<h2 class="font-headline-md text-[16px] font-semibold text-on-surface">Lokasi Anda</h2>
<p class="font-body-md text-body-md text-on-surface-variant mt-1" id="loc-detail-out">Mengambil lokasi...</p>
</div>
<div class="bg-error-container text-on-error-container px-2 py-1 rounded-full font-label-sm text-label-sm flex items-center gap-1 shrink-0">
<span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;">warning</span>
Di luar radius kantor
</div>
</div>
<!-- Map Preview -->
<div class="w-full h-32 rounded-lg relative overflow-hidden mb-3 border border-border flex items-center justify-center bg-surface-container">
<div id="gps-loading-out" class="flex flex-col items-center justify-center gap-1 text-on-surface-variant">
<div id="gps-loading-out-anim" class="w-14 h-14" aria-hidden="true"></div>
<span class="font-label-sm text-label-sm">Mengambil lokasi...</span>
</div>
<div id="gps-pin-out" class="hidden absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-danger drop-shadow-md">
<span class="material-symbols-outlined text-[32px]" style="font-variation-settings: 'FILL' 1;">location_on</span>
</div>
<div id="gps-error-out" class="hidden absolute inset-0 flex-col items-center justify-center gap-2 text-center px-4 bg-surface-container">
<span class="material-symbols-outlined text-error text-[28px]">location_off</span>
<p class="font-label-sm text-label-sm text-error" id="gps-error-text-out">GPS tidak tersedia.</p>
<button type="button" onclick="retryGpsOut()" class="mt-1 text-primary font-label-sm text-label-sm underline">Coba lagi</button>
</div>
</div>
<div class="flex justify-between items-center font-label-sm text-label-sm">
<span class="text-danger font-semibold flex items-center gap-1" id="dist-out">
<span class="material-symbols-outlined text-[14px]">error</span> Di luar radius
</span>
<span class="text-outline flex items-center gap-1" id="acc-out">
<span class="material-symbols-outlined text-[14px]">my_location</span> Akurasi: --
</span>
</div>
</div>
<!-- Verification Section -->
<div class="mx-container-margin bg-surface border border-border rounded-xl shadow-sm p-unit-md mb-unit-lg">
<h2 class="font-headline-md text-[16px] font-semibold text-on-surface mb-unit-sm">Verifikasi Foto</h2>
@error('photo')<p class="text-error font-label-sm text-label-sm mb-2">{{ $message }}</p>@enderror
<div id="camera-error-out" class="hidden bg-error-container text-on-error-container rounded-lg px-4 py-3 mb-2 font-label-sm text-label-sm items-center gap-2">
<span class="material-symbols-outlined text-[16px] shrink-0">videocam_off</span>
<span id="camera-error-text-out">Kamera tidak tersedia.</span>
</div>
<div id="camera-area-out" class="w-full h-32 border-2 border-dashed border-outline-variant rounded-lg relative overflow-hidden bg-surface-container-low">
<div id="camera-loading-out" class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-on-surface-variant z-10">
<span class="material-symbols-outlined text-[32px]">photo_camera</span>
<span class="font-body-md text-body-md">Memulai kamera...</span>
</div>
<video id="selfie-video-out" autoplay playsinline muted class="hidden absolute inset-0 w-full h-full object-cover z-10"></video>
<div id="capture-overlay-out" class="hidden absolute inset-0 flex items-end justify-center pb-2 z-20">
<button type="button" id="capture-btn-out" class="bg-primary text-on-primary rounded-full px-4 py-1.5 font-label-md text-label-md flex items-center gap-1.5 shadow-md active:scale-95 transition-all">
<span class="material-symbols-outlined text-[16px]" style="font-variation-settings:'FILL' 1;">photo_camera</span>
Ambil Selfie
</button>
</div>
<img id="photo-preview-out" class="hidden absolute inset-0 w-full h-full object-cover z-10" alt="Pratinjau selfie">
<button type="button" id="photo-retake-out" class="hidden absolute bottom-2 right-2 z-20 bg-surface/80 rounded-full px-2 py-1 font-label-sm text-label-sm text-primary backdrop-blur-sm">Ambil Ulang</button>
</div>
<canvas id="selfie-canvas-out" class="hidden"></canvas>
</div>
<!-- Required Inputs Section -->
<div class="mx-container-margin">
<label class="block font-label-md text-label-md text-on-surface mb-2">
Alasan absen di luar radius <span class="text-error">*</span>
</label>
@error('reason')<p class="text-error font-label-sm text-label-sm mb-1">{{ $message }}</p>@enderror
<textarea id="reason-out" name="reason" rows="4" maxlength="500"
    class="w-full border border-outline-variant rounded-lg p-3 font-body-md text-body-md text-on-surface bg-surface focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all placeholder:text-outline resize-none"
    placeholder="Contoh: Rapat klien, bekerja dari rumah... (min. 10 karakter)">{{ old('reason') }}</textarea>
<div class="mt-2 flex flex-col gap-1">
<p class="font-body-md text-[13px] text-on-surface-variant">Presensi ini akan dikirim untuk review HR.</p>
<p class="font-label-md text-label-md text-warning flex items-center gap-1 mt-1">
<span class="material-symbols-outlined text-[16px]">pending</span> Status: Menunggu Review HR
</p>
</div>
<button id="submit-btn-out" type="submit" disabled
    class="w-full bg-primary text-on-primary disabled:opacity-40 disabled:cursor-not-allowed py-3.5 rounded-lg font-label-md text-label-md mt-8 hover:bg-on-primary-fixed-variant transition-colors shadow-sm active:scale-[0.98]">
Kirim untuk Review HR
</button>
</div>
</main>
</form>
<!-- BottomNavBar (from Shared Components JSON) -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center px-2 py-3 bg-surface border-t border-border backdrop-blur-md shadow-lg">
<!-- Inactive: Home -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high rounded-lg transition-all duration-200" onclick="window.location.href='/employee/dashboard'">
<span class="material-symbols-outlined" data-icon="home">home</span>
<span class="font-label-sm text-label-sm mt-1">Beranda</span>
</button>
<!-- Active: Attendance -->
<button class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1 active:scale-90 transition-all duration-200" onclick="window.location.href='/attendance/checkin'">
<span class="material-symbols-outlined" data-icon="schedule" style="font-variation-settings: 'FILL' 1;">schedule</span>
<span class="font-label-sm text-label-sm mt-1">Presensi</span>
</button>
<!-- Inactive: Leave -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high rounded-lg transition-all duration-200" onclick="window.location.href='/leave/history'">
<span class="material-symbols-outlined" data-icon="event_note">event_note</span>
<span class="font-label-sm text-label-sm mt-1">Cuti</span>
</button>
<!-- Inactive: Payslip -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high rounded-lg transition-all duration-200" onclick="window.location.href='/payslip/detail'">
<span class="material-symbols-outlined" data-icon="payments">payments</span>
<span class="font-label-sm text-label-sm mt-1">Slip Gaji</span>
</button>
<!-- Inactive: Profile -->
<button class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high rounded-lg transition-all duration-200" onclick="window.location.href='{{ route('my.profile') }}'">
<span class="material-symbols-outlined" data-icon="person">person</span>
<span class="font-label-sm text-label-sm mt-1">Profil</span>
</button>
</nav>
</div>
<script src="/assets/lottie/vendor/lottie-web.min.js"></script>
<script src="/assets/lottie/lottie-helper.js"></script>
<script>
(function() {
    mountLottie('gps-loading-out-anim', '/assets/lottie/gps-loading.json', { loop: true, autoplay: true });

    let gpsOk = false, photoOk = false;
    function trySubmit() {
        const btn = document.getElementById('submit-btn-out');
        if (btn) btn.disabled = !(gpsOk && photoOk);
    }
    // Clock
    function tick() {
        const n = new Date();
        let h = n.getHours(), m = n.getMinutes(), ap = h>=12?'PM':'AM';
        h = h%12||12;
        const tel = document.getElementById('time-out');
        const del = document.getElementById('date-out');
        if (tel) tel.innerText = h+':'+(m<10?'0'+m:m)+' '+ap;
        if (del) del.innerText = n.toLocaleDateString('id-ID',{weekday:'long',day:'numeric',month:'short',year:'numeric'});
    }
    setInterval(tick,1000); tick();
    // GPS
    function startGpsOut() {
        if (!navigator.geolocation) {
            showGpsErrorOut('Perangkat/browser ini tidak mendukung GPS.');
            return;
        }
        const loadEl = document.getElementById('gps-loading-out');
        const pinEl  = document.getElementById('gps-pin-out');
        const errEl  = document.getElementById('gps-error-out');
        const locEl  = document.getElementById('loc-detail-out');
        if (errEl)  { errEl.classList.add('hidden'); errEl.classList.remove('flex'); }
        if (pinEl)  pinEl.classList.add('hidden');
        if (loadEl) loadEl.classList.remove('hidden');
        if (locEl)  locEl.innerText = 'Mengambil lokasi...';

        navigator.geolocation.getCurrentPosition(function(p) {
            document.getElementById('lat-out').value = p.coords.latitude;
            document.getElementById('lng-out').value = p.coords.longitude;
            const acc = Math.round(p.coords.accuracy);
            if (loadEl) loadEl.classList.add('hidden');
            if (pinEl)  pinEl.classList.remove('hidden');
            const accEl = document.getElementById('acc-out');
            if (accEl) accEl.innerHTML = '<span class="material-symbols-outlined text-[14px]">my_location</span> Akurasi: ' + acc + 'm';
            if (locEl) locEl.innerText = 'GPS aktif — akurasi ' + acc + 'm';
            gpsOk = true; trySubmit();
        }, function(e) {
            const msgs = {
                1: 'Izin lokasi ditolak. Aktifkan akses lokasi di pengaturan browser.',
                2: 'Posisi tidak tersedia. Pastikan GPS perangkat aktif.',
                3: 'Timeout GPS. Tekan "Coba lagi".',
            };
            showGpsErrorOut(msgs[e.code] || 'Gagal mendapatkan lokasi.');
        }, {enableHighAccuracy: true, timeout: 12000, maximumAge: 0});
    }

    function showGpsErrorOut(msg) {
        const loadEl = document.getElementById('gps-loading-out');
        const errEl  = document.getElementById('gps-error-out');
        const txtEl  = document.getElementById('gps-error-text-out');
        const pinEl  = document.getElementById('gps-pin-out');
        const locEl  = document.getElementById('loc-detail-out');
        if (loadEl) loadEl.classList.add('hidden');
        if (pinEl)  pinEl.classList.add('hidden');
        if (errEl)  { errEl.classList.remove('hidden'); errEl.classList.add('flex'); }
        if (txtEl)  txtEl.innerText = msg;
        if (locEl)  locEl.innerText = 'GPS tidak tersedia';
    }

    window.retryGpsOut = function() { startGpsOut(); };
    // Camera / Selfie (getUserMedia)
    let cameraStreamOut = null;
    let capturedBlobOut = null;

    function showCameraErrorOut(msg) {
        const errDiv = document.getElementById('camera-error-out');
        const errTxt = document.getElementById('camera-error-text-out');
        const area   = document.getElementById('camera-area-out');
        if (errTxt) errTxt.innerText = msg;
        if (errDiv) { errDiv.classList.remove('hidden'); errDiv.classList.add('flex'); }
        if (area)   area.classList.add('hidden');
    }

    function startCameraOut() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showCameraErrorOut('Browser ini tidak mendukung akses kamera langsung.');
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
            .then(function(stream) {
                cameraStreamOut = stream;
                const video   = document.getElementById('selfie-video-out');
                const loading = document.getElementById('camera-loading-out');
                const overlay = document.getElementById('capture-overlay-out');
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
                showCameraErrorOut(msg);
            });
    }

    const captureBtnOut = document.getElementById('capture-btn-out');
    if (captureBtnOut) {
        captureBtnOut.addEventListener('click', function() {
            const video   = document.getElementById('selfie-video-out');
            const canvas  = document.getElementById('selfie-canvas-out');
            const preview = document.getElementById('photo-preview-out');
            const retake  = document.getElementById('photo-retake-out');
            const overlay = document.getElementById('capture-overlay-out');
            if (!video || !canvas || video.videoWidth === 0) return;

            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
            if (preview) { preview.src = dataUrl; preview.classList.remove('hidden'); }
            if (overlay) overlay.classList.add('hidden');
            video.classList.add('hidden');
            if (retake)  retake.classList.remove('hidden');

            if (cameraStreamOut) {
                cameraStreamOut.getTracks().forEach(function(t) { t.stop(); });
                cameraStreamOut = null;
            }

            canvas.toBlob(function(blob) {
                capturedBlobOut = blob;
                photoOk = true;
                trySubmit();
            }, 'image/jpeg', 0.9);
        });
    }

    const retakeBtnOut = document.getElementById('photo-retake-out');
    if (retakeBtnOut) {
        retakeBtnOut.addEventListener('click', function() {
            const preview = document.getElementById('photo-preview-out');
            if (preview) { preview.src = ''; preview.classList.add('hidden'); }
            retakeBtnOut.classList.add('hidden');
            capturedBlobOut = null;
            photoOk = false;
            trySubmit();
            startCameraOut();
        });
    }

    // Intercept form submit — send captured blob via fetch
    const outsideForm = document.getElementById('checkin-outside-form');
    if (outsideForm) {
        outsideForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!capturedBlobOut) return;

            const btn = document.getElementById('submit-btn-out');
            if (btn) btn.disabled = true;

            const csrf     = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const formData = new FormData(outsideForm);
            formData.set('photo', capturedBlobOut, 'selfie.jpg');

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
            });
        });
    }

    // Start GPS and camera on page load
    startGpsOut();
    startCameraOut();
})();
</script>
</body></html>
