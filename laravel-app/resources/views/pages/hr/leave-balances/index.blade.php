<!DOCTYPE html>
<html class="light" lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Saldo Cuti Tahunan - HRIS</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script id="tailwind-config">
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      "colors": {
        "primary":"#3525cd","on-primary":"#ffffff","primary-container":"#4f46e5",
        "surface":"#F9FAFB","surface-container-low":"#f0f3ff","surface-container":"#e7eefe",
        "on-surface":"#151c27","on-surface-variant":"#464555","outline":"#777587","outline-variant":"#c7c4d8",
        "border":"#E5E7EB","background":"#f9f9ff","success":"#10B981","danger":"#EF4444","error":"#ba1a1a","warning":"#F59E0B"
      },
      "fontFamily": {"body":["Inter"],"label":["Inter"],"headline":["Inter"]},
      "fontSize": {
        "headline-md-mobile":["18px",{"lineHeight":"24px","fontWeight":"600"}],
        "body-md":["14px",{"lineHeight":"20px","fontWeight":"400"}],
        "label-md":["12px",{"lineHeight":"16px","letterSpacing":"0.05em","fontWeight":"600"}],
        "label-sm":["11px",{"lineHeight":"14px","fontWeight":"500"}]
      }
    }
  }
}
</script>
<style>
  body { font-family: 'Inter', sans-serif; }
  .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
</style>
</head>
<body class="bg-background text-on-surface flex flex-col items-center min-h-screen">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-outline-variant shadow-sm flex items-center justify-between px-4 h-16">
<a href="{{ route('settings.index') }}" class="p-2 rounded-full hover:bg-surface-container transition-colors active:scale-95">
  <span class="material-symbols-outlined text-primary">arrow_back</span>
</a>
<h1 class="font-bold text-primary text-headline-md-mobile">Saldo Cuti Tahunan</h1>
<div class="w-10"></div>
</header>

<main class="w-full max-w-[390px] mt-16 mb-8 px-4 py-4 flex flex-col gap-3">
  @if(session('success'))
  <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-body-md">
    {{ session('success') }}
  </div>
  @endif

  <form method="GET" action="{{ route('hr.leave-balances.index') }}" class="flex gap-2">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIK..."
      class="flex-1 h-11 px-3 bg-white border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary-container">
    <input type="number" name="year" value="{{ $year }}" min="2000" max="2100"
      class="w-24 h-11 px-3 bg-white border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary-container">
    <button type="submit" class="h-11 px-4 bg-primary text-on-primary rounded-lg text-label-md">Cari</button>
  </form>

  <p class="text-label-sm text-on-surface-variant">Menampilkan hak cuti tahun {{ $year }} untuk pegawai aktif.</p>

  @forelse($employees as $employee)
  @php $entitlement = $entitlements->get($employee->id); @endphp
  <a href="{{ route('hr.leave-balances.edit', ['employee' => $employee->id, 'year' => $year]) }}"
    class="bg-white border border-border rounded-xl shadow-sm p-4 flex items-center justify-between">
    <div class="flex flex-col">
      <span class="font-body-md text-body-md font-medium">{{ $employee->user->name }}</span>
      <span class="font-label-sm text-label-sm text-on-surface-variant">NIK {{ $employee->nik }} &middot; Mulai kerja {{ $employee->join_date?->format('d M Y') ?? 'tidak valid' }}</span>
      <span class="font-label-sm text-label-sm text-on-surface-variant mt-0.5">
        @if(! $entitlement['eligible'])
          Belum Memenuhi Masa Kerja 12 Bulan
        @elseif($entitlement['months_remaining'] < 12)
          Hak Prorata &middot; {{ $entitlement['final_entitlement'] }} hari
        @else
          Hak Penuh &middot; {{ $entitlement['final_entitlement'] }} hari
        @endif
      </span>
    </div>
    <span class="material-symbols-outlined text-outline">chevron_right</span>
  </a>
  @empty
  <div class="bg-white border border-border rounded-xl shadow-sm p-6 text-center">
    <p class="font-body-md text-body-md text-on-surface-variant">Tidak ada pegawai ditemukan.</p>
  </div>
  @endforelse

  <div>{{ $employees->links() }}</div>
</main>
</body></html>
