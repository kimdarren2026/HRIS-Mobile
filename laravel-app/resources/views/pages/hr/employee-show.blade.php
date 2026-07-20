<!DOCTYPE html><html class="light" lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>{{ $employee->user?->name ?? $employee->nik }} - HRIS Mobile App</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
        "surface":"#F9FAFB","surface-container-lowest":"#ffffff","surface-container-low":"#f0f3ff",
        "surface-container":"#e7eefe","surface-container-high":"#e2e8f8","surface-container-highest":"#dce2f3",
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
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
</head>
<body class="bg-surface text-on-surface min-h-screen max-w-[390px] mx-auto overflow-x-hidden pb-24">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-outline-variant shadow-sm flex justify-between items-center px-container-margin h-16">
  <a href="{{ route('employees.index') }}" class="text-primary p-1">
    <span class="material-symbols-outlined">arrow_back</span>
  </a>
  <h1 class="font-headline-md text-headline-md font-bold text-primary truncate">Detail Pegawai</h1>
  <a href="{{ route('employees.edit', $employee) }}" class="text-primary hover:bg-surface-container-low p-2 rounded-full transition-colors active:scale-95">
    <span class="material-symbols-outlined">edit</span>
  </a>
</header>

<main class="mt-16 px-container-margin pt-unit-lg space-y-unit-lg">

  @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 font-body-md flex items-center gap-2">
      <span class="material-symbols-outlined text-[18px] text-success">check_circle</span>
      {{ session('success') }}
    </div>
  @endif

  {{-- Profile Header --}}
  <section class="flex flex-col items-center text-center gap-unit-sm">
    <div class="w-24 h-24 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-3xl">
      {{ strtoupper(substr($employee->user?->name ?? '?', 0, 2)) }}
    </div>
    <h2 class="font-headline-lg text-headline-lg text-on-surface">{{ $employee->user?->name ?? '—' }}</h2>
    <p class="font-label-md text-label-md text-outline tracking-wider">NIK: {{ $employee->nik }}</p>
    <p class="font-body-lg text-primary-container font-semibold">{{ $employee->position?->name ?? '—' }}</p>
    <p class="font-body-md text-on-surface-variant">{{ $employee->department?->name ?? '—' }}</p>
    @php
      $statusColor = match($employee->employment_status) {
        'active'     => 'bg-green-100 text-success',
        'probation'  => 'bg-yellow-100 text-warning',
        'resigned'   => 'bg-red-100 text-danger',
        'terminated' => 'bg-surface-container text-on-surface-variant',
        default      => 'bg-surface-container text-on-surface-variant',
      };
      $statusLabel = match($employee->employment_status) {
        'active'     => 'Aktif',
        'probation'  => 'Masa Percobaan',
        'resigned'   => 'Mengundurkan Diri',
        'terminated' => 'Diberhentikan',
        default      => $employee->employment_status,
      };
    @endphp
    <span class="inline-flex items-center px-3 py-1 rounded-full {{ $statusColor }} font-status-badge">
      {{ $statusLabel }}
    </span>
  </section>

  {{-- Employment Info --}}
  <section>
    <h3 class="font-label-md text-label-md text-outline uppercase tracking-widest mb-unit-sm pl-1">Kepegawaian</h3>
    <div class="bg-surface-container-lowest rounded-xl border border-border overflow-hidden divide-y divide-border shadow-sm">
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">badge</span>
        </div>
        <div>
          <p class="text-[10px] text-outline font-semibold uppercase">Tanggal Bergabung</p>
          <p class="font-body-md text-on-surface">{{ $employee->join_date?->translatedFormat('d M Y') ?? '—' }}</p>
        </div>
      </div>
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">domain</span>
        </div>
        <div>
          <p class="text-[10px] text-outline font-semibold uppercase">Departemen</p>
          <p class="font-body-md text-on-surface">{{ $employee->department?->name ?? '—' }}</p>
        </div>
      </div>
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">work</span>
        </div>
        <div>
          <p class="text-[10px] text-outline font-semibold uppercase">Posisi</p>
          <p class="font-body-md text-on-surface">{{ $employee->position?->name ?? '—' }}</p>
        </div>
      </div>
    </div>
  </section>

  {{-- Contact Info --}}
  <section>
    <h3 class="font-label-md text-label-md text-outline uppercase tracking-widest mb-unit-sm pl-1">Kontak</h3>
    <div class="bg-surface-container-lowest rounded-xl border border-border overflow-hidden divide-y divide-border shadow-sm">
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">mail</span>
        </div>
        <div>
          <p class="text-[10px] text-outline font-semibold uppercase">Email</p>
          <p class="font-body-md text-on-surface">{{ $employee->user?->email ?? '—' }}</p>
        </div>
      </div>
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">call</span>
        </div>
        <div>
          <p class="text-[10px] text-outline font-semibold uppercase">Telepon</p>
          <p class="font-body-md text-on-surface">{{ $employee->phone_number }}</p>
        </div>
      </div>
      @if($employee->address)
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">home</span>
        </div>
        <div>
          <p class="text-[10px] text-outline font-semibold uppercase">Alamat</p>
          <p class="font-body-md text-on-surface">{{ $employee->address }}</p>
        </div>
      </div>
      @endif
    </div>
  </section>

  {{-- Bank Info --}}
  @if($employee->bank_name || $employee->bank_account_number)
  <section>
    <h3 class="font-label-md text-label-md text-outline uppercase tracking-widest mb-unit-sm pl-1">Bank</h3>
    <div class="bg-surface-container-lowest rounded-xl border border-border overflow-hidden divide-y divide-border shadow-sm">
      @if($employee->bank_name)
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">account_balance</span>
        </div>
        <div>
          <p class="text-[10px] text-outline font-semibold uppercase">Bank</p>
          <p class="font-body-md text-on-surface">{{ $employee->bank_name }}</p>
        </div>
      </div>
      @endif
      @if($employee->bank_account_number)
      <div class="p-unit-md flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
          <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
        </div>
        <div>
          <p class="text-[10px] text-outline font-semibold uppercase">Rekening</p>
          <p class="font-body-md text-on-surface">**** {{ substr($employee->bank_account_number, -4) }}</p>
        </div>
      </div>
      @endif
    </div>
  </section>
  @endif

  {{-- Edit Button --}}
  <a href="{{ route('employees.edit', $employee) }}"
    class="w-full h-14 bg-primary text-on-primary font-semibold rounded-xl shadow-lg flex items-center justify-center gap-2 hover:opacity-90 active:scale-95 transition-all">
    <span class="material-symbols-outlined text-[20px]">edit</span>
    Ubah Pegawai
  </a>

</main>

</body></html>
