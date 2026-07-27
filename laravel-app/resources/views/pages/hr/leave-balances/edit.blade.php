<!DOCTYPE html>
<html class="light" lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Saldo Awal Cuti - HRIS</title>
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
<a href="{{ route('hr.leave-balances.index', ['year' => $year]) }}" class="p-2 rounded-full hover:bg-surface-container transition-colors active:scale-95">
  <span class="material-symbols-outlined text-primary">arrow_back</span>
</a>
<h1 class="font-bold text-primary text-headline-md-mobile">Saldo Awal Cuti</h1>
<div class="w-10"></div>
</header>

<main class="w-full max-w-[390px] mt-16 mb-8 px-4 py-4 flex flex-col gap-3">
  @if($errors->has('general'))
  <div class="bg-red-50 border border-red-200 text-error rounded-lg px-4 py-3 text-body-md">{{ $errors->first('general') }}</div>
  @endif

  <section class="bg-white border border-border rounded-xl shadow-sm p-4 flex flex-col gap-1">
    <span class="font-body-md text-body-md font-semibold">{{ $employee->user->name }}</span>
    <span class="font-label-sm text-label-sm text-on-surface-variant">NIK {{ $employee->nik }}</span>
    <span class="font-label-sm text-label-sm text-on-surface-variant">Tanggal Mulai Kerja: {{ $employee->join_date?->format('d M Y') ?? 'Tidak valid — perlu koreksi HR' }}</span>
  </section>

  @if($pooledTypeNames->count() > 1)
  <div class="bg-surface-container-low border border-outline-variant rounded-lg px-4 py-2 text-label-sm text-on-surface-variant">
    Satu pool cuti tahunan ini dipakai bersama oleh: {{ $pooledTypeNames->implode(', ') }}.
  </div>
  @endif

  <section class="bg-primary-container/10 border border-primary-container/30 rounded-xl p-4 flex flex-col gap-2">
    <h2 class="text-label-md text-primary uppercase tracking-wider">Hak Cuti {{ $year }} &mdash; {{ $leaveType->display_name }}</h2>
    <div class="grid grid-cols-2 gap-2 text-body-md">
      <div class="flex flex-col">
        <span class="text-label-sm text-on-surface-variant">Status</span>
        <span class="font-semibold">
          @if(! $entitlement['eligible']) Belum Memenuhi Masa Kerja 12 Bulan
          @elseif($entitlement['months_remaining'] < 12) Hak Prorata
          @else Hak Penuh
          @endif
        </span>
      </div>
      <div class="flex flex-col">
        <span class="text-label-sm text-on-surface-variant">Hak Tahun Ini</span>
        <span class="font-semibold">{{ $entitlement['final_entitlement'] }} hari</span>
      </div>
      <div class="flex flex-col">
        <span class="text-label-sm text-on-surface-variant">Tanggal Mulai Hak</span>
        <span class="font-semibold">{{ $entitlement['eligibility_date']?->format('d M Y') ?? '-' }}</span>
      </div>
      <div class="flex flex-col">
        <span class="text-label-sm text-on-surface-variant">Saldo Tersimpan</span>
        <span class="font-semibold">{{ $balance ? $balance->remaining.' hari' : 'Belum ada' }}</span>
      </div>
    </div>
    <p class="text-label-sm text-on-surface-variant border-t border-primary-container/20 pt-2">{{ $entitlement['reason'] }}</p>
  </section>

  @if($hasApprovedUsage)
  <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-4 py-3 text-body-md">
    Saldo tahun {{ $year }} untuk pool cuti tahunan ini sudah memiliki cuti yang disetujui, sehingga penyesuaian saldo awal tidak dapat dilakukan melalui halaman ini.
  </div>
  @elseif(! $entitlement['eligible'])
  <div class="bg-surface-container-low border border-outline-variant rounded-lg px-4 py-3 text-body-md text-on-surface-variant">
    Pegawai belum memenuhi masa kerja 12 bulan untuk tahun {{ $year }}; tidak ada hak cuti yang dapat ditetapkan.
  </div>
  @else
  <form method="POST" action="{{ route('hr.leave-balances.update', $employee) }}" class="bg-white border border-border rounded-xl shadow-sm p-4 flex flex-col gap-3">
    @csrf
    @method('PUT')
    <input type="hidden" name="year" value="{{ $year }}">

    <div class="flex flex-col gap-1">
      <label class="text-label-md text-on-surface-variant" for="pre_system_used_days">Pemakaian Sebelum HRIS (hari)</label>
      <input type="number" step="0.5" min="0" name="pre_system_used_days" id="pre_system_used_days"
        value="{{ old('pre_system_used_days', $balance->pre_system_used_days ?? 0) }}"
        class="h-11 px-3 bg-surface border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary-container" required>
      @error('pre_system_used_days')<span class="text-label-sm text-error">{{ $message }}</span>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <label class="text-label-md text-on-surface-variant" for="opening_adjustment">Penyesuaian Tambahan (hari)</label>
      <input type="number" step="0.5" name="opening_adjustment" id="opening_adjustment"
        value="{{ old('opening_adjustment', $balance->opening_adjustment ?? 0) }}"
        class="h-11 px-3 bg-surface border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary-container">
      <p class="text-label-sm text-on-surface-variant">Nilai <strong>positif menambah</strong> saldo, nilai <strong>negatif mengurangi</strong> saldo. Isi 0 jika tidak ada penyesuaian.</p>
      @error('opening_adjustment')<span class="text-label-sm text-error">{{ $message }}</span>@enderror
    </div>

    <div class="flex flex-col gap-1">
      <label class="text-label-md text-on-surface-variant" for="effective_date">Tanggal Efektif</label>
      <input type="date" name="effective_date" id="effective_date"
        value="{{ old('effective_date', $balance->effective_date?->toDateString()) }}"
        class="h-11 px-3 bg-surface border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary-container">
    </div>

    <div class="flex flex-col gap-1">
      <label class="text-label-md text-on-surface-variant" for="reason">Catatan / Alasan (wajib)</label>
      <textarea name="reason" id="reason" rows="3" minlength="10" required
        class="p-3 bg-surface border border-outline-variant rounded-lg text-body-md focus:outline-none focus:ring-2 focus:ring-primary-container">{{ old('reason', $balance->reason ?? '') }}</textarea>
      @error('reason')<span class="text-label-sm text-error">{{ $message }}</span>@enderror
    </div>

    <button type="submit" class="h-12 bg-primary text-on-primary rounded-lg text-body-md font-semibold">Simpan Saldo Awal</button>
  </form>
  @endif
</main>
</body></html>
