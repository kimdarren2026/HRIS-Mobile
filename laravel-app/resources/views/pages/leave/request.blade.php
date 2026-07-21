<!DOCTYPE html><html class="light" lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<title>Pengajuan Cuti - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    "surface-container-highest": "#dce2f3",
                    "primary-container": "#4f46e5",
                    "outline-variant": "#c7c4d8",
                    "primary": "#3525cd",
                    "outline": "#777587",
                    "secondary-container": "#6860ef",
                    "on-tertiary-fixed-variant": "#7b2f00",
                    "primary-fixed-dim": "#c3c0ff",
                    "warning": "#F59E0B",
                    "on-surface": "#151c27",
                    "on-secondary-container": "#fffbff",
                    "surface-bright": "#f9f9ff",
                    "surface-tint": "#4d44e3",
                    "inverse-on-surface": "#ebf1ff",
                    "tertiary-fixed-dim": "#ffb695",
                    "on-primary-fixed-variant": "#3323cc",
                    "background": "#f9f9ff",
                    "inverse-surface": "#2a313d",
                    "tertiary": "#7e3000",
                    "on-background": "#151c27",
                    "secondary": "#4e45d5",
                    "error": "#ba1a1a",
                    "surface-container": "#e7eefe",
                    "on-surface-variant": "#464555",
                    "tertiary-container": "#a44100",
                    "surface-container-high": "#e2e8f8",
                    "surface": "#F9FAFB",
                    "inverse-primary": "#c3c0ff",
                    "on-tertiary-fixed": "#351000",
                    "on-secondary-fixed": "#100069",
                    "on-secondary": "#ffffff",
                    "surface-container-lowest": "#ffffff",
                    "secondary-fixed": "#e3dfff",
                    "error-container": "#ffdad6",
                    "on-primary-fixed": "#0f0069",
                    "on-error": "#ffffff",
                    "success": "#10B981",
                    "primary-fixed": "#e2dfff",
                    "surface-dim": "#d3daea",
                    "on-secondary-fixed-variant": "#372abf",
                    "border": "#E5E7EB",
                    "on-primary": "#ffffff",
                    "on-primary-container": "#dad7ff",
                    "tertiary-fixed": "#ffdbcc",
                    "secondary-fixed-dim": "#c3c0ff",
                    "surface-container-low": "#f0f3ff",
                    "danger": "#EF4444",
                    "on-tertiary-container": "#ffd2be",
                    "on-tertiary": "#ffffff",
                    "surface-variant": "#dce2f3",
                    "on-error-container": "#93000a"
                },
                "borderRadius": {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
                },
                "spacing": {
                    "container-margin": "16px",
                    "unit-xs": "4px",
                    "unit-md": "16px",
                    "unit-lg": "24px",
                    "unit-xl": "32px",
                    "unit-sm": "8px",
                    "card-gap": "12px"
                },
                "fontFamily": {
                    "body-md": ["Inter"],
                    "body-lg": ["Inter"],
                    "status-badge": ["Inter"],
                    "headline-md": ["Inter"],
                    "label-sm": ["Inter"],
                    "label-md": ["Inter"],
                    "headline-lg": ["Inter"]
                },
                "fontSize": {
                    "body-md": ["14px", { "lineHeight": "20px", "fontWeight": "400" }],
                    "body-lg": ["16px", { "lineHeight": "24px", "fontWeight": "400" }],
                    "status-badge": ["12px", { "lineHeight": "12px", "fontWeight": "700" }],
                    "headline-md": ["20px", { "lineHeight": "28px", "fontWeight": "600" }],
                    "label-sm": ["11px", { "lineHeight": "14px", "fontWeight": "500" }],
                    "label-md": ["12px", { "lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600" }],
                    "headline-lg": ["24px", { "lineHeight": "32px", "fontWeight": "700" }]
                }
            }
        }
    }
</script>
<style>
    body { -webkit-tap-highlight-color: transparent; min-height: max(884px, 100dvh); }
</style>
</head>
<body class="bg-surface text-on-surface min-h-screen max-w-[390px] mx-auto overflow-x-hidden pb-40">

<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex items-center justify-between px-container-margin h-16 bg-surface-container-lowest shadow-sm border-b border-outline-variant">
    <button class="w-10 h-10 flex items-center justify-center text-primary hover:bg-surface-container-low active:opacity-70 transition-opacity rounded-full" onclick="window.location.href='/leave/history'">
        <span class="material-symbols-outlined">arrow_back</span>
    </button>
    <h1 class="text-headline-md font-headline-md font-bold text-primary">Pengajuan Cuti</h1>
    <div class="w-10 h-10"></div>
</header>

<main class="pt-20 px-container-margin flex flex-col gap-unit-lg">

    {{-- Success / Error flash --}}
    <x-flash-message class="!mb-0" />

    @if($errors->any())
    <div class="bg-error-container border border-error/30 text-error rounded-lg px-4 py-3 font-body-md text-body-md">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Leave Balance Card --}}
    <section class="bg-primary-container text-on-primary-container p-unit-md rounded-xl shadow-sm flex flex-col gap-unit-sm">
        <h2 class="text-label-md font-label-md uppercase tracking-wider opacity-90">Saldo Cuti {{ $year }}</h2>
        @if($balanceRows->isEmpty())
            @if($annualLeaveEligible)
            <p class="text-body-md font-body-md opacity-80">Belum ada data saldo cuti.</p>
            @else
            <p class="text-body-md font-body-md opacity-80">Cuti tahunan tersedia setelah masa kerja minimal 12 bulan.</p>
            @endif
        @else
        <div class="flex flex-col gap-1" id="balance-list">
            @foreach($balanceRows as $row)
            <div class="balance-row flex items-center justify-between">
                <span class="text-body-md opacity-90">{{ $row['label'] }}</span>
                <span class="text-headline-md font-bold">{{ $row['remaining'] }} hari tersisa</span>
            </div>
            @endforeach
        </div>
            @if(! $annualLeaveEligible)
            <p class="text-label-sm font-label-sm opacity-80">Cuti tahunan tersedia setelah masa kerja minimal 12 bulan.</p>
            @endif
        @endif
    </section>

    {{-- Form --}}
    <form class="flex flex-col gap-unit-md bg-surface-container-lowest p-unit-md rounded-xl shadow-sm border border-border"
          method="POST" action="/leave/request" enctype="multipart/form-data">
        @csrf

        {{-- Leave Type --}}
        <div class="flex flex-col gap-unit-xs">
            <label class="text-label-md font-label-md text-on-surface-variant" for="leave_type_id">Jenis Cuti</label>
            <div class="relative">
                <select class="w-full h-12 px-unit-md bg-surface border border-outline-variant rounded-lg text-body-md font-body-md text-on-surface appearance-none focus:outline-none focus:ring-2 focus:ring-primary-container focus:border-transparent"
                        id="leave_type_id" name="leave_type_id" required>
                    <option value="">Pilih jenis cuti...</option>
                    @foreach($leaveTypes as $type)
                    <option value="{{ $type->id }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                        {{ $type->display_name }}
                    </option>
                    @endforeach
                </select>
                <span class="material-symbols-outlined absolute right-unit-md top-1/2 -translate-y-1/2 text-outline pointer-events-none">arrow_drop_down</span>
            </div>
        </div>

        {{-- Duration type --}}
        <div class="flex flex-col gap-unit-xs">
            <label class="text-label-md font-label-md text-on-surface-variant">Durasi</label>
            <div class="flex gap-unit-md">
                <label class="flex-1 flex items-center gap-2 px-unit-md h-12 bg-surface border border-outline-variant rounded-lg cursor-pointer has-[:checked]:border-primary-container has-[:checked]:bg-primary-container/10">
                    <input type="radio" name="duration_type" value="FULL_DAY" id="duration_full" {{ old('duration_type', 'FULL_DAY') === 'FULL_DAY' ? 'checked' : '' }}>
                    <span class="text-body-md font-body-md text-on-surface">Sehari Penuh</span>
                </label>
                <label class="flex-1 flex items-center gap-2 px-unit-md h-12 bg-surface border border-outline-variant rounded-lg cursor-pointer has-[:checked]:border-primary-container has-[:checked]:bg-primary-container/10">
                    <input type="radio" name="duration_type" value="HALF_DAY" id="duration_half" {{ old('duration_type') === 'HALF_DAY' ? 'checked' : '' }}>
                    <span class="text-body-md font-body-md text-on-surface">Setengah Hari</span>
                </label>
            </div>
        </div>

        {{-- Dates --}}
        <div class="flex gap-unit-md">
            <div class="flex flex-col gap-unit-xs w-1/2">
                <label class="text-label-md font-label-md text-on-surface-variant" for="start_date">Tanggal Mulai</label>
                <input class="w-full h-12 px-unit-md bg-surface border border-outline-variant rounded-lg text-body-md font-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary-container focus:border-transparent"
                       id="start_date" name="start_date" type="date"
                       min="{{ now()->toDateString() }}"
                       value="{{ old('start_date') }}" required>
            </div>
            <div class="flex flex-col gap-unit-xs w-1/2">
                <label class="text-label-md font-label-md text-on-surface-variant" for="end_date">Tanggal Selesai</label>
                <input class="w-full h-12 px-unit-md bg-surface border border-outline-variant rounded-lg text-body-md font-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary-container focus:border-transparent"
                       id="end_date" name="end_date" type="date"
                       min="{{ now()->toDateString() }}"
                       value="{{ old('end_date') }}" required>
            </div>
        </div>

        {{-- Duration preview --}}
        <p id="duration-preview" class="text-label-sm font-label-sm text-primary hidden text-center -mt-2"></p>
        <p id="half-day-note" class="text-label-sm font-label-sm text-warning hidden text-center -mt-2">
            Cuti setengah hari tetap diperhitungkan sebagai 1 hari dari saldo cuti. Hanya dapat diajukan untuk satu tanggal kerja (bukan Sabtu, Minggu, atau hari libur).
        </p>

        {{-- Reason --}}
        <div class="flex flex-col gap-unit-xs">
            <label class="text-label-md font-label-md text-on-surface-variant" for="reason">Alasan</label>
            <textarea class="w-full p-unit-md bg-surface border border-outline-variant rounded-lg text-body-md font-body-md text-on-surface focus:outline-none focus:ring-2 focus:ring-primary-container focus:border-transparent resize-none"
                      id="reason" name="reason" rows="4"
                      placeholder="Berikan alasan singkat untuk cuti Anda..." required minlength="10" maxlength="1000">{{ old('reason') }}</textarea>
        </div>

        {{-- Attachment --}}
        <div class="flex flex-col gap-unit-xs">
            <label class="text-label-md font-label-md text-on-surface-variant">Lampiran (Opsional)</label>
            <label class="border-2 border-dashed border-outline-variant rounded-lg p-unit-md flex flex-col items-center justify-center gap-unit-sm bg-surface hover:bg-surface-container-low transition-colors cursor-pointer">
                <span class="material-symbols-outlined text-outline text-[32px]">upload_file</span>
                <p class="text-body-md font-body-md text-on-surface-variant text-center" id="attach-label">
                    Ketuk untuk unggah<br><span class="text-label-sm font-label-sm text-outline">PDF, JPG, atau PNG maks. 5MB</span>
                </p>
                <input accept=".pdf,.jpg,.jpeg,.png" class="hidden" type="file" name="attachment" id="attachment-input">
            </label>
        </div>
    </form>

    <div class="bg-surface-container-low border border-outline-variant rounded-lg p-unit-md flex flex-col gap-1">
        <p class="text-label-sm font-label-sm text-on-surface-variant">
            Saldo cuti akan dikurangi hanya setelah disetujui HR.
        </p>
        <p class="text-label-sm font-label-sm text-on-surface-variant">
            Cuti tahunan hanya dapat digunakan setelah masa kerja minimal 12 bulan.
        </p>
        <p class="text-label-sm font-label-sm text-on-surface-variant">
            Penggunaan cuti tahunan maksimal 2 hari kerja per bulan, kecuali dengan persetujuan khusus.
        </p>
        <p class="text-label-sm font-label-sm text-on-surface-variant">
            Hari Sabtu, Minggu, dan Libur Nasional tidak dihitung sebagai pengurang saldo cuti tahunan.
        </p>
        <p class="text-label-sm font-label-sm text-on-surface-variant">
            Cuti setengah hari tetap diperhitungkan sebagai 1 hari dari saldo cuti.
        </p>
        <p class="text-label-sm font-label-sm text-on-surface-variant">
            Sisa cuti tahunan yang tidak digunakan akan gugur pada akhir tahun kalender.
        </p>
    </div>

    <button class="w-full h-12 bg-primary-container text-on-primary-container rounded-lg text-body-lg font-body-lg font-semibold shadow-sm hover:bg-secondary-container active:scale-95 transition-all flex items-center justify-center gap-unit-sm"
            type="button" onclick="document.querySelector('form').submit()">
        <span>Ajukan Cuti</span>
        <span class="material-symbols-outlined text-[20px]">send</span>
    </button>
</main>

<!-- BottomNavBar -->
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] mx-auto h-16 bg-surface-container-lowest border-t border-outline-variant flex items-center justify-around px-unit-sm z-50">
    <a class="flex flex-col items-center gap-unit-xs text-on-surface-variant hover:text-primary transition-colors" href="/employee/dashboard">
        <span class="material-symbols-outlined">home</span>
        <span class="text-label-sm">Beranda</span>
    </a>
    <a class="flex flex-col items-center gap-unit-xs text-on-surface-variant hover:text-primary transition-colors" href="/attendance/checkin">
        <span class="material-symbols-outlined">schedule</span>
        <span class="text-label-sm">Presensi</span>
    </a>
    <a class="flex flex-col items-center gap-unit-xs text-primary" href="/leave/history">
        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">event_note</span>
        <span class="text-label-sm font-bold">Cuti</span>
    </a>
    <a class="flex flex-col items-center gap-unit-xs text-on-surface-variant hover:text-primary transition-colors relative" href="/payslip/detail">
        <span class="absolute -top-0.5 right-1 w-2 h-2 rounded-full bg-warning" aria-hidden="true" title="Segera Hadir"></span>
        <span class="material-symbols-outlined">payments</span>
        <span class="text-label-sm">Slip Gaji</span>
    </a>
    <a class="flex flex-col items-center gap-unit-xs text-on-surface-variant hover:text-primary transition-colors" href="{{ route('my.profile') }}">
        <span class="material-symbols-outlined">person</span>
        <span class="text-label-sm">Profil</span>
    </a>
</nav>

<script>
const startInput   = document.getElementById('start_date');
const endInput     = document.getElementById('end_date');
const preview      = document.getElementById('duration-preview');
const halfDayNote  = document.getElementById('half-day-note');
const durationFull = document.getElementById('duration_full');
const durationHalf = document.getElementById('duration_half');

function isHalfDay() {
    return durationHalf.checked;
}

function updateDuration() {
    if (isHalfDay()) {
        preview.classList.add('hidden');
        halfDayNote.classList.remove('hidden');
        return;
    }
    halfDayNote.classList.add('hidden');

    const s = new Date(startInput.value);
    const e = new Date(endInput.value);
    if (!startInput.value || !endInput.value || e < s) {
        preview.classList.add('hidden');
        return;
    }
    const days = Math.round((e - s) / 86400000) + 1;
    preview.textContent = days + ' hari diajukan';
    preview.classList.remove('hidden');
}

function syncHalfDayEndDate() {
    if (isHalfDay()) {
        endInput.value = startInput.value;
        endInput.readOnly = true;
        endInput.classList.add('opacity-60', 'bg-surface-container-low');
    } else {
        endInput.readOnly = false;
        endInput.classList.remove('opacity-60', 'bg-surface-container-low');
    }
    updateDuration();
}

startInput.addEventListener('change', function() {
    if (isHalfDay()) {
        endInput.value = this.value;
    } else if (endInput.value && endInput.value < this.value) {
        endInput.value = this.value;
    }
    updateDuration();
});
endInput.addEventListener('change', updateDuration);
durationFull.addEventListener('change', syncHalfDayEndDate);
durationHalf.addEventListener('change', syncHalfDayEndDate);
syncHalfDayEndDate();

// Show filename when file selected
document.getElementById('attachment-input').addEventListener('change', function() {
    const label = document.getElementById('attach-label');
    if (this.files.length) {
        label.innerHTML = '<span class="font-semibold text-primary">' + this.files[0].name + '</span>';
    } else {
        label.innerHTML = 'Ketuk untuk unggah<br><span class="text-label-sm font-label-sm text-outline">PDF, JPG, atau PNG maks. 5MB</span>';
    }
});

// Restore old start/end if validation failed
if ('{{ old("start_date") }}') endInput.min = '{{ old("start_date") }}';
updateDuration();
</script>
</body></html>
