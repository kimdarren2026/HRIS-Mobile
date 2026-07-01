<!DOCTYPE html><html class="light" lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Profil Saya - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
        "surface":"#F9FAFB","surface-container-lowest":"#ffffff","surface-container-low":"#f0f3ff",
        "surface-container":"#e7eefe","surface-container-high":"#e2e8f8",
        "on-surface":"#151c27","on-surface-variant":"#464555","outline":"#777587","outline-variant":"#c7c4d8",
        "primary":"#3525cd","primary-container":"#4f46e5","primary-fixed":"#e2dfff","primary-fixed-dim":"#c3c0ff",
        "on-primary":"#ffffff","on-primary-container":"#dad7ff",
        "secondary":"#4e45d5","secondary-container":"#6860ef","on-secondary":"#ffffff","on-secondary-container":"#fffbff",
        "border":"#E5E7EB","background":"#f9f9ff","success":"#10B981","warning":"#F59E0B","danger":"#EF4444"
      },
      "borderRadius": {"DEFAULT":"0.25rem","lg":"0.5rem","xl":"0.75rem","full":"9999px"},
      "spacing": {"unit-xs":"4px","unit-sm":"8px","unit-md":"16px","unit-lg":"24px","unit-xl":"32px","card-gap":"12px","container-margin":"16px"},
      "fontFamily": {"label-sm":["Inter"],"label-md":["Inter"],"body-md":["Inter"],"body-lg":["Inter"],"headline-md":["Inter"],"headline-lg":["Inter"],"status-badge":["Inter"]},
      "fontSize": {
        "label-sm":["11px",{"lineHeight":"14px","fontWeight":"500"}],
        "label-sm-mobile":["10px",{"lineHeight":"12px","fontWeight":"500"}],
        "label-md":["12px",{"lineHeight":"16px","letterSpacing":"0.05em","fontWeight":"600"}],
        "body-md":["14px",{"lineHeight":"20px","fontWeight":"400"}],
        "body-lg":["16px",{"lineHeight":"24px","fontWeight":"400"}],
        "headline-md":["20px",{"lineHeight":"28px","fontWeight":"600"}],
        "headline-lg":["24px",{"lineHeight":"32px","fontWeight":"700"}],
        "status-badge":["12px",{"lineHeight":"12px","fontWeight":"700"}]
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
  .hide-scrollbar::-webkit-scrollbar { display: none; }
  .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
</head>
<body class="bg-surface text-on-surface min-h-screen flex flex-col items-center">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-between items-center px-container-margin h-16 bg-surface border-b border-border shadow-sm">
  @php
  $dashboardUrl = match(auth()->user()->role) {
      'finance'    => '/finance/dashboard',
      'admin_hr'   => '/admin/dashboard',
      'super_admin'=> '/admin/dashboard',
      default      => '/employee/dashboard',
  };
  @endphp
  <a href="{{ $dashboardUrl }}" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container transition-colors active:scale-95">
    <span class="material-symbols-outlined text-primary">arrow_back</span>
  </a>
  <h1 class="text-headline-md font-headline-md font-bold text-primary">Profil Saya</h1>
  <div class="w-10"></div>
</header>

<main class="w-full max-w-[390px] pt-16 pb-28 px-container-margin overflow-y-auto overflow-x-hidden flex flex-col gap-unit-lg">

  {{-- Profile Header --}}
  <section class="mt-unit-lg flex flex-col items-center text-center">
    <div class="relative mb-4">
      <div class="w-32 h-32 rounded-full border-4 border-white shadow-lg overflow-hidden bg-secondary-container flex items-center justify-center">
        @if($employee->photo_path)
          <img class="w-full h-full object-cover" src="{{ asset('storage/' . $employee->photo_path) }}" alt="{{ $employee->user->name }}">
        @else
          <span class="text-on-secondary-container font-bold text-4xl">
            {{ strtoupper(substr($employee->user->name, 0, 2)) }}
          </span>
        @endif
      </div>
      @php
        $statusColor = match($employee->employment_status) {
          'active'    => 'bg-success',
          'probation' => 'bg-warning',
          default     => 'bg-on-surface-variant',
        };
      @endphp
      <div class="absolute bottom-1 right-1 {{ $statusColor }} text-white px-3 py-1 rounded-full border-2 border-white shadow-sm flex items-center justify-center">
        <span class="font-status-badge text-status-badge uppercase">{{ $employee->employment_status }}</span>
      </div>
    </div>
    <h2 class="font-headline-lg text-headline-lg text-on-surface">{{ $employee->user->name }}</h2>
    <p class="font-label-md text-label-md text-outline tracking-wider mb-2">NIK: {{ $employee->nik }}</p>
    <div class="flex flex-col gap-1 items-center">
      <span class="font-body-lg text-body-lg text-primary-container font-semibold">{{ $employee->position?->name ?? '—' }}</span>
      <span class="font-body-md text-body-md text-on-surface-variant">{{ $employee->department?->name ?? '—' }}</span>
    </div>
  </section>

  {{-- HR-managed Profile Notice --}}
  <section class="bg-surface-container-low rounded-xl border border-border p-unit-md flex gap-3 shadow-sm">
    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
      <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
    </div>
    <div class="flex flex-col gap-1">
      <p class="font-label-md text-label-md text-on-surface">Profil dikelola oleh HR</p>
      <p class="font-body-md text-body-md text-on-surface-variant">Hubungi HR untuk memperbarui data pribadi atau data terkait penggajian</p>
    </div>
  </section>

  {{-- General Information --}}
  <section class="flex flex-col gap-unit-md">
    <h3 class="font-label-md text-label-md text-outline uppercase tracking-widest pl-1">Informasi Umum</h3>
    <div class="bg-white rounded-xl border border-border overflow-hidden divide-y divide-border shadow-sm">
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">mail</span>
        </div>
        <div class="flex flex-col">
          <span class="text-[10px] text-outline font-semibold uppercase">Email</span>
          <span class="text-body-md font-body-md text-on-surface">{{ $employee->user->email }}</span>
        </div>
      </div>
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">call</span>
        </div>
        <div class="flex flex-col">
          <span class="text-[10px] text-outline font-semibold uppercase">Telepon</span>
          <span class="text-body-md font-body-md text-on-surface">{{ $employee->phone_number }}</span>
        </div>
      </div>
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">calendar_today</span>
        </div>
        <div class="flex flex-col">
          <span class="text-[10px] text-outline font-semibold uppercase">Tanggal Bergabung</span>
          <span class="text-body-md font-body-md text-on-surface">{{ $employee->join_date?->format('M d, Y') }}</span>
        </div>
      </div>
      @if($employee->address)
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">home</span>
        </div>
        <div class="flex flex-col">
          <span class="text-[10px] text-outline font-semibold uppercase">Alamat</span>
          <span class="text-body-md font-body-md text-on-surface">{{ $employee->address }}</span>
        </div>
      </div>
      @endif
      @if($employee->bank_account_number)
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
        </div>
        <div class="flex flex-col">
          <span class="text-[10px] text-outline font-semibold uppercase">Rekening Bank</span>
          <span class="text-body-md font-body-md text-on-surface">**** {{ substr($employee->bank_account_number, -4) }}</span>
        </div>
      </div>
      @endif
    </div>
  </section>

  {{-- Quick Links --}}
  <section class="flex flex-col gap-unit-md">
    <h3 class="font-label-md text-label-md text-outline uppercase tracking-widest pl-1">Tautan Cepat</h3>
    <div class="bg-white rounded-xl border border-border overflow-hidden divide-y divide-border shadow-sm">
      <a href="{{ route('my.payroll.index') }}" class="p-unit-md flex items-center justify-between hover:bg-surface-container-low transition-colors">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-[20px]">payments</span>
          </div>
          <span class="text-body-md font-body-md text-on-surface">Slip Gaji Saya</span>
        </div>
        <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
      </a>
      <a href="/attendance/history" class="p-unit-md flex items-center justify-between hover:bg-surface-container-low transition-colors">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-[20px]">schedule</span>
          </div>
          <span class="text-body-md font-body-md text-on-surface">Riwayat Presensi</span>
        </div>
        <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
      </a>
      <a href="/leave/history" class="p-unit-md flex items-center justify-between hover:bg-surface-container-low transition-colors">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
            <span class="material-symbols-outlined text-[20px]">event_note</span>
          </div>
          <span class="text-body-md font-body-md text-on-surface">Pengajuan Cuti</span>
        </div>
        <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
      </a>
    </div>
  </section>

  {{-- Logout --}}
  <section>
    <form action="{{ route('logout') }}" method="POST">
      @csrf
      <button class="w-full h-12 text-danger font-semibold rounded-xl active:opacity-70 transition-opacity flex items-center justify-center gap-2 border border-red-200 bg-red-50" type="submit">
        <span class="material-symbols-outlined text-[20px]">logout</span>
        Keluar
      </button>
    </form>
  </section>

</main>

{{-- Bottom Nav --}}
<nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 flex justify-around items-center px-2 py-3 bg-surface border-t border-border backdrop-blur-md shadow-lg h-20">
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high transition-all active:scale-90" href="{{ $dashboardUrl }}">
    <span class="material-symbols-outlined">home</span>
    <span class="font-label-sm text-label-sm-mobile mt-1">Beranda</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high transition-all active:scale-90" href="/attendance/checkin">
    <span class="material-symbols-outlined">schedule</span>
    <span class="font-label-sm text-label-sm-mobile mt-1">Presensi</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high transition-all active:scale-90" href="/leave/history">
    <span class="material-symbols-outlined">event_note</span>
    <span class="font-label-sm text-label-sm-mobile mt-1">Cuti</span>
  </a>
  <a class="flex flex-col items-center justify-center text-on-surface-variant px-4 py-1 hover:bg-surface-container-high transition-all active:scale-90" href="{{ route('my.payroll.index') }}">
    <span class="material-symbols-outlined">payments</span>
    <span class="font-label-sm text-label-sm-mobile mt-1">Slip Gaji</span>
  </a>
  <a class="flex flex-col items-center justify-center bg-primary-container text-on-primary-container rounded-full px-4 py-1 active:scale-90 transition-all" href="{{ route('my.profile') }}">
    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">person</span>
    <span class="font-label-sm text-label-sm-mobile mt-1">Profil</span>
  </a>
</nav>

</body></html>
