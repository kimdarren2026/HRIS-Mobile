<!DOCTYPE html><html class="light" lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Slip Gaji – {{ $payrollRecord->payrollPeriod->name }} - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          "colors": {
                  "secondary-fixed": "#e3dfff","surface-container-low": "#f0f3ff","inverse-surface": "#2a313d",
                  "surface-variant": "#dce2f3","surface": "#F9FAFB","surface-container-high": "#e2e8f8",
                  "surface-container-lowest": "#ffffff","on-surface": "#151c27","surface-container": "#e7eefe",
                  "warning": "#F59E0B","on-secondary": "#ffffff","background": "#f9f9ff",
                  "primary-fixed": "#e2dfff","on-surface-variant": "#464555","danger": "#EF4444",
                  "primary-container": "#4f46e5","surface-container-highest": "#dce2f3","surface-tint": "#4d44e3",
                  "on-primary-container": "#dad7ff","on-primary": "#ffffff","primary": "#3525cd",
                  "on-error": "#ffffff","success": "#10B981","border": "#E5E7EB",
                  "primary-fixed-dim": "#c3c0ff","secondary-container": "#6860ef","secondary": "#4e45d5",
                  "outline-variant": "#c7c4d8","outline": "#777587","error": "#ba1a1a",
                  "on-background": "#151c27","surface-dim": "#d3daea","error-container": "#ffdad6",
                  "on-error-container": "#93000a","surface-bright": "#f9f9ff","secondary-fixed-dim": "#c3c0ff"
          },
          "borderRadius": {"DEFAULT": "0.25rem","lg": "0.5rem","xl": "0.75rem","full": "9999px"},
          "spacing": {"unit-xs": "4px","card-gap": "12px","unit-md": "16px","container-margin": "16px","unit-xl": "32px","unit-sm": "8px","unit-lg": "24px"},
          "fontFamily": {"label-md": ["Inter"],"label-sm": ["Inter"],"headline-md": ["Inter"],"body-lg": ["Inter"],"status-badge": ["Inter"],"headline-lg": ["Inter"],"body-md": ["Inter"]},
          "fontSize": {
            "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "600"}],
            "label-sm": ["11px", {"lineHeight": "14px", "fontWeight": "500"}],
            "headline-md": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
            "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
            "status-badge": ["12px", {"lineHeight": "12px", "fontWeight": "700"}],
            "headline-lg": ["24px", {"lineHeight": "32px", "fontWeight": "700"}],
            "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}]
          }
        },
      },
    }
</script>
<style>
    body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .safe-bottom { padding-bottom: env(safe-area-inset-bottom); }
</style>
</head>
<body class="bg-surface text-on-surface overflow-x-hidden w-full max-w-[390px] mx-auto min-h-screen relative shadow-2xl">

@php
  $period = $payrollRecord->payrollPeriod;
  $employee = $payrollRecord->employee;
  $gross = (float) $payrollRecord->basic_salary + (float) $payrollRecord->allowance
         + (float) $payrollRecord->bonus + (float) $payrollRecord->overtime;
  $totalDeductions = (float) $payrollRecord->deduction + (float) $payrollRecord->late_deduction
                   + (float) $payrollRecord->attendance_deduction + (float) $payrollRecord->tax_bpjs;
@endphp

<!-- TopAppBar -->
<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-border shadow-sm h-16 flex items-center px-container-margin gap-3">
  <a href="{{ route('my.payroll.index') }}" class="text-primary p-1 transition-colors active:opacity-70">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <div class="flex-1 min-w-0">
    <h1 class="font-headline-md text-headline-md font-bold text-primary truncate">{{ $period->name }}</h1>
    <p class="font-label-sm text-label-sm text-on-surface-variant">
      {{ $period->start_date->translatedFormat('M d') }} – {{ $period->end_date->translatedFormat('M d, Y') }}
    </p>
  </div>
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  {{-- Net Pay Hero --}}
  <section class="bg-primary-container rounded-xl p-6 flex flex-col items-center gap-2 relative overflow-hidden">
    <div class="absolute -right-6 -bottom-6 opacity-10">
      <span class="material-symbols-outlined text-[128px] text-white">payments</span>
    </div>
    <p class="font-label-md text-label-md text-on-primary-container/70 z-10">Total Gaji Bersih</p>
    <h2 class="font-headline-lg text-headline-lg text-white z-10">
      Rp {{ number_format((float) $payrollRecord->net_salary, 0, ',', '.') }}
    </h2>
    @if($period->pay_date)
      <p class="font-label-sm text-label-sm text-on-primary-container/60 z-10">
        Tanggal Bayar: {{ $period->pay_date->translatedFormat('d M Y') }}
      </p>
    @endif
  </section>

  {{-- Employee Info --}}
  <section class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
    <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Informasi Karyawan</h3>
    <div class="flex flex-col gap-2">
      <div class="flex justify-between items-center">
        <span class="font-body-md text-body-md text-on-surface-variant">Nama</span>
        <span class="font-body-md text-body-md text-on-surface font-semibold">{{ $employee->user->name ?? '—' }}</span>
      </div>
      <div class="flex justify-between items-center">
        <span class="font-body-md text-body-md text-on-surface-variant">NIK</span>
        <span class="font-body-md text-body-md text-on-surface">{{ $employee->nik }}</span>
      </div>
      @if($employee->position)
      <div class="flex justify-between items-center">
        <span class="font-body-md text-body-md text-on-surface-variant">Jabatan</span>
        <span class="font-body-md text-body-md text-on-surface">{{ $employee->position->name }}</span>
      </div>
      @endif
      @if($employee->department)
      <div class="flex justify-between items-center">
        <span class="font-body-md text-body-md text-on-surface-variant">Departemen</span>
        <span class="font-body-md text-body-md text-on-surface">{{ $employee->department->name }}</span>
      </div>
      @endif
      <div class="flex justify-between items-center">
        <span class="font-body-md text-body-md text-on-surface-variant">Hari Kerja</span>
        <span class="font-body-md text-body-md text-on-surface">{{ $payrollRecord->attendance_days }} hari</span>
      </div>
    </div>
  </section>

  {{-- Earnings --}}
  <section class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
    <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Pendapatan</h3>
    <div class="flex flex-col gap-2">
      @php
        $earnings = [
            'Gaji Pokok'   => (float) $payrollRecord->basic_salary,
            'Tunjangan'    => (float) $payrollRecord->allowance,
            'Bonus'        => (float) $payrollRecord->bonus,
            'Lembur'       => (float) $payrollRecord->overtime,
        ];
      @endphp
      @foreach($earnings as $label => $amount)
        @if($amount > 0)
          <div class="flex justify-between items-center">
            <span class="font-body-md text-body-md text-on-surface-variant">{{ $label }}</span>
            <span class="font-body-md text-body-md text-on-surface">Rp {{ number_format($amount, 0, ',', '.') }}</span>
          </div>
        @endif
      @endforeach
      <div class="border-t border-border pt-2 flex justify-between items-center">
        <span class="font-label-md text-label-md text-on-surface">Total Pendapatan</span>
        <span class="font-label-md text-label-md text-on-surface">Rp {{ number_format($gross, 0, ',', '.') }}</span>
      </div>
    </div>
  </section>

  {{-- Deductions --}}
  <section class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
    <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Potongan</h3>
    <div class="flex flex-col gap-2">
      @php
        $deductions = [
            'Potongan Umum'      => (float) $payrollRecord->deduction,
            'Potongan Terlambat' => (float) $payrollRecord->late_deduction,
            'Potongan Kehadiran' => (float) $payrollRecord->attendance_deduction,
            'Pajak & BPJS'       => (float) $payrollRecord->tax_bpjs,
        ];
      @endphp
      @foreach($deductions as $label => $amount)
        @if($amount > 0)
          <div class="flex justify-between items-center">
            <span class="font-body-md text-body-md text-on-surface-variant">{{ $label }}</span>
            <span class="font-body-md text-body-md text-danger">– Rp {{ number_format($amount, 0, ',', '.') }}</span>
          </div>
        @endif
      @endforeach
      <div class="border-t border-border pt-2 flex justify-between items-center">
        <span class="font-label-md text-label-md text-on-surface">Total Potongan</span>
        <span class="font-label-md text-label-md text-danger">– Rp {{ number_format($totalDeductions, 0, ',', '.') }}</span>
      </div>
    </div>
  </section>

  {{-- Net Summary --}}
  <section class="bg-surface-container rounded-xl border border-border p-4 flex justify-between items-center">
    <span class="font-headline-md text-headline-md text-on-surface">Gaji Bersih</span>
    <span class="font-headline-md text-headline-md text-primary">Rp {{ number_format((float) $payrollRecord->net_salary, 0, ',', '.') }}</span>
  </section>

  {{-- Print Payslip --}}
  <a href="{{ route('my.payroll.print', $payrollRecord) }}"
     class="flex items-center justify-center gap-2 w-full bg-primary text-white py-3.5 rounded-xl font-label-md text-label-md active:opacity-90 transition-opacity">
    <span class="material-symbols-outlined">print</span>
    Cetak Slip Gaji
  </a>

</main>

<!-- BottomNavBar -->
<nav class="bg-surface fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center px-2 py-3 border-t border-border backdrop-blur-md mx-auto">
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 rounded-full active:scale-90 transition-all duration-200" href="/employee/dashboard">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-sm text-label-sm mt-0.5">{{ __('common.nav_home') }}</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 rounded-full active:scale-90 transition-all duration-200" href="/attendance/checkin">
    <span class="material-symbols-outlined">schedule</span>
    <span class="font-label-sm text-label-sm mt-0.5">{{ __('common.nav_attendance') }}</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 rounded-full active:scale-90 transition-all duration-200" href="/leave/history">
    <span class="material-symbols-outlined">event_note</span>
    <span class="font-label-sm text-label-sm mt-0.5">{{ __('common.nav_leave') }}</span>
  </a>
  <a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container px-4 py-1 rounded-full active:scale-90 transition-all duration-200" href="/my/payroll">
    <span class="material-symbols-outlined">payments</span>
    <span class="font-label-sm text-label-sm mt-0.5">{{ __('common.nav_payslip') }}</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 rounded-full active:scale-90 transition-all duration-200" href="{{ route('my.profile') }}">
    <span class="material-symbols-outlined">person</span>
    <span class="font-label-sm text-label-sm mt-0.5">{{ __('common.nav_profile') }}</span>
  </a>
</nav>

</body></html>
