{{--
    Phase 60B — Dashboard Disiplin Kehadiran (super_admin only).

    Charts are plain HTML/CSS bars rendered server-side: no chart library, no CDN
    script, no third-party inline script, and no user value ever reaches raw
    JavaScript. Every value below is printed through {{ }}, so employee-supplied
    text (work plan, work result, out-of-radius reason, approval note) is escaped
    and can never execute as markup.

    Page styling uses the application's locally compiled CSS plus scoped
    fallbacks for this page's custom design tokens. No third-party network
    access is required at runtime.
--}}
<!DOCTYPE html><html class="light" lang="id"><head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Dashboard Disiplin Kehadiran - HRIS Mobile App</title>
@vite('resources/css/app.css')
<style>
    body.attendance-discipline {
        font-family: ui-sans-serif, system-ui, sans-serif;
        -webkit-tap-highlight-color: transparent;
    }
    .attendance-discipline .material-symbols-outlined { display: none; }
    .attendance-discipline .local-icon { display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem; line-height: 1; font-weight: 700; }

    /* Scoped fallbacks for custom utilities that are not part of the shared theme. */
    .attendance-discipline .bg-primary { background-color: #3525cd; }
    .attendance-discipline .bg-primary\/40 { background-color: rgb(53 37 205 / 40%); }
    .attendance-discipline .bg-secondary { background-color: #4e45d5; }
    .attendance-discipline .bg-secondary-fixed { background-color: #e3dfff; }
    .attendance-discipline .bg-success { background-color: #10b981; }
    .attendance-discipline.bg-surface,
    .attendance-discipline .bg-surface { background-color: #f9fafb; }
    .attendance-discipline .bg-surface\/80 { background-color: rgb(249 250 251 / 80%); }
    .attendance-discipline .bg-surface-container-high { background-color: #e2e8f8; }
    .attendance-discipline .bg-surface-container-low { background-color: #f0f3ff; }
    .attendance-discipline .border-border { border-color: #e5e7eb; }
    .attendance-discipline .border-border\/60 { border-color: rgb(229 231 235 / 60%); }
    .attendance-discipline .border-outline-variant\/40 { border-color: rgb(199 196 216 / 40%); }
    .attendance-discipline .text-danger { color: #ef4444; }
    .attendance-discipline.text-on-surface,
    .attendance-discipline .text-on-surface { color: #151c27; }
    .attendance-discipline .text-on-surface-variant { color: #464555; }
    .attendance-discipline .text-outline { color: #777587; }
    .attendance-discipline .text-primary { color: #3525cd; }
    .attendance-discipline .text-success { color: #10b981; }
    .attendance-discipline .text-warning { color: #f59e0b; }
    .attendance-discipline .focus\:ring-primary:focus { --tw-ring-color: #3525cd; }

    .attendance-discipline .font-body-md,
    .attendance-discipline .font-headline-lg,
    .attendance-discipline .font-headline-md,
    .attendance-discipline .font-label-md,
    .attendance-discipline .font-label-sm { font-family: inherit; }
    .attendance-discipline .text-body-md { font-size: 14px; line-height: 20px; font-weight: 400; }
    .attendance-discipline .text-headline-lg { font-size: 24px; line-height: 32px; font-weight: 700; }
    .attendance-discipline .text-headline-md { font-size: 20px; line-height: 28px; font-weight: 600; }
    .attendance-discipline .text-label-md { font-size: 12px; line-height: 16px; letter-spacing: .05em; font-weight: 600; }
    .attendance-discipline .text-label-sm { font-size: 11px; line-height: 14px; font-weight: 500; }

    .attendance-discipline .gap-card-gap { gap: 12px; }
    .attendance-discipline .gap-unit-lg { gap: 24px; }
    .attendance-discipline .gap-unit-sm { gap: 8px; }
    .attendance-discipline .p-unit-md { padding: 16px; }
    .attendance-discipline .px-container-margin { padding-inline: 16px; }
    .attendance-discipline .px-unit-xs { padding-inline: 4px; }
    .attendance-discipline .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
    .attendance-discipline .-mx-4 { margin-inline: -1rem; }
    .attendance-discipline .align-top { vertical-align: top; }
    .attendance-discipline .list-none { list-style-type: none; }
    .attendance-discipline .max-w-\[220px\] { max-width: 220px; }
    .attendance-discipline .min-w-\[1400px\] { min-width: 1400px; }
    .attendance-discipline .min-w-max { min-width: max-content; }
    .attendance-discipline .py-8 { padding-block: 2rem; }
    .attendance-discipline .text-\[9px\] { font-size: 9px; }
    .attendance-discipline .whitespace-pre-line { white-space: pre-line; }
    .attendance-discipline .group[open] .group-open\:hidden { display: none; }
</style>
</head>
<body class="attendance-discipline bg-surface text-on-surface overflow-x-hidden w-full max-w-[390px] mx-auto min-h-screen relative shadow-2xl">

<header class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-[390px] z-50 bg-surface border-b border-border shadow-sm h-16 flex items-center px-container-margin gap-3">
  {{-- Phase 60C: named route, never history.back() — the dashboard is reachable
       from the bottom nav, a filter form GET, and /reports, so "back" has to be
       one predictable destination rather than whatever the last page was. --}}
  <a href="{{ route('admin.dashboard') }}" class="text-primary p-1" aria-label="Kembali ke dashboard admin">
    <span class="local-icon" aria-hidden="true">←</span>
  </a>
  <h1 class="font-headline-md text-headline-md font-bold text-primary flex-1 truncate">Disiplin Kehadiran</h1>
  <span class="font-label-sm text-label-sm text-on-surface-variant">Super Admin</span>
</header>

<main class="pt-20 pb-28 px-container-margin flex flex-col gap-unit-lg">

  {{-- Page title + mandatory scope disclaimer --}}
  <section class="flex flex-col gap-2 mt-2">
    <h2 class="font-headline-lg text-headline-lg text-on-surface">Dashboard Disiplin Kehadiran</h2>
    <div class="bg-surface-container-low rounded-xl border border-outline-variant/40 p-unit-md flex items-start gap-2">
      <span class="local-icon text-outline text-[18px] mt-0.5" aria-hidden="true">i</span>
      <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
        Ringkasan ini menggambarkan data kedisiplinan dan kelengkapan kehadiran, bukan penilaian performa karyawan.
      </p>
    </div>
  </section>

  <x-validation-errors variant="settings" />

  {{-- Filters --}}
  <form method="GET" action="{{ route('admin.attendance-discipline.index') }}"
        class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-unit-sm">
    <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">Filter</p>

    <div class="grid grid-cols-2 gap-unit-sm">
      <label class="flex flex-col gap-1">
        <span class="font-label-sm text-label-sm text-on-surface-variant">Tanggal Mulai</span>
        <input type="date" name="start_date" value="{{ $filter->startDate->format('Y-m-d') }}"
               class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </label>
      <label class="flex flex-col gap-1">
        <span class="font-label-sm text-label-sm text-on-surface-variant">Tanggal Selesai</span>
        <input type="date" name="end_date" value="{{ $filter->endDate->format('Y-m-d') }}"
               class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </label>
    </div>

    <label class="flex flex-col gap-1">
      <span class="font-label-sm text-label-sm text-on-surface-variant">Pegawai</span>
      <select name="employee_id" class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
        <option value="">Semua Pegawai</option>
        @foreach($employees as $employee)
          <option value="{{ $employee->id }}" @selected($filter->employeeId === $employee->id)>
            {{ $employee->user?->name ?? 'Tanpa Nama' }} ({{ $employee->nik }})
          </option>
        @endforeach
      </select>
    </label>

    <label class="flex flex-col gap-1">
      <span class="font-label-sm text-label-sm text-on-surface-variant">Departemen</span>
      <select name="department_id" class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
        <option value="">Semua Departemen</option>
        @foreach($departments as $department)
          <option value="{{ $department->id }}" @selected($filter->departmentId === $department->id)>{{ $department->name }}</option>
        @endforeach
      </select>
    </label>

    <label class="flex flex-col gap-1">
      <span class="font-label-sm text-label-sm text-on-surface-variant">Status Kehadiran</span>
      <select name="status" class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
        <option value="">Semua Status</option>
        @foreach($statuses as $value => $label)
          <option value="{{ $value }}" @selected($filter->status === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </label>

    <div class="grid grid-cols-2 gap-unit-sm">
      <label class="flex flex-col gap-1">
        <span class="font-label-sm text-label-sm text-on-surface-variant">Klasifikasi Radius</span>
        <select name="radius" class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
          <option value="all" @selected($filter->radius === 'all')>Semua</option>
          <option value="inside" @selected($filter->radius === 'inside')>Dalam Radius</option>
          <option value="outside" @selected($filter->radius === 'outside')>Luar Radius</option>
        </select>
      </label>
      <label class="flex flex-col gap-1">
        <span class="font-label-sm text-label-sm text-on-surface-variant">Kelengkapan Checkout</span>
        <select name="checkout" class="border border-border rounded-lg px-3 py-2 font-body-md text-body-md bg-white focus:outline-none focus:ring-2 focus:ring-primary text-sm">
          <option value="all" @selected($filter->checkout === 'all')>Semua</option>
          <option value="complete" @selected($filter->checkout === 'complete')>Sudah Checkout</option>
          <option value="incomplete" @selected($filter->checkout === 'incomplete')>Belum Checkout</option>
        </select>
      </label>
    </div>

    <div class="flex gap-unit-sm mt-1">
      <button type="submit" class="flex-1 bg-primary text-white py-2.5 rounded-lg font-label-md text-label-md">Terapkan Filter</button>
      <a href="{{ route('admin.attendance-discipline.index') }}"
         class="flex-1 text-center bg-surface-container-high text-on-surface py-2.5 rounded-lg font-label-md text-label-md">Reset</a>
    </div>

    <a href="{{ route('admin.attendance-discipline.export', $filter->toQueryString()) }}"
       class="flex items-center justify-center gap-2 bg-success text-white py-2.5 rounded-lg font-label-md text-label-md">
      <span class="local-icon text-[18px]" aria-hidden="true">↓</span>
      Download XLSX
    </a>
  </form>

  {{-- Period echo --}}
  <p class="font-label-sm text-label-sm text-on-surface-variant">
    Periode {{ $filter->startDate->format('d/m/Y') }} – {{ $filter->endDate->format('d/m/Y') }}
    · {{ number_format($summary['total_records']) }} data kehadiran
  </p>

  {{-- Summary cards --}}
  @php
    $cards = [
      ['label' => 'Total Presensi',        'value' => $summary['total_records'],          'tone' => 'text-primary'],
      ['label' => 'Disetujui',             'value' => $summary['total_approved'],         'tone' => 'text-success'],
      ['label' => 'Menunggu Review',       'value' => $summary['total_pending'],          'tone' => 'text-warning'],
      ['label' => 'Ditolak',               'value' => $summary['total_rejected'],         'tone' => 'text-danger'],
      ['label' => 'Dalam Radius',          'value' => $summary['total_inside'],           'tone' => 'text-success'],
      ['label' => 'Luar Radius',           'value' => $summary['total_outside'],          'tone' => 'text-warning'],
      ['label' => 'Radius Tidak Diketahui','value' => $summary['total_unknown_radius'],   'tone' => 'text-on-surface-variant'],
      ['label' => 'Sudah Checkout',        'value' => $summary['total_checked_out'],      'tone' => 'text-success'],
      ['label' => 'Belum Checkout',        'value' => $summary['total_not_checked_out'],  'tone' => 'text-danger'],
      ['label' => 'Memiliki Rencana Kerja','value' => $summary['total_with_work_plan'],   'tone' => 'text-primary'],
      ['label' => 'Memiliki Hasil Pekerjaan','value' => $summary['total_with_work_result'],'tone' => 'text-primary'],
    ];
  @endphp

  <section class="grid grid-cols-2 gap-card-gap">
    @foreach($cards as $card)
      <div class="bg-white rounded-xl border border-border shadow-sm p-3 flex flex-col gap-1 min-w-0">
        <p class="font-label-sm text-label-sm text-on-surface-variant break-words">{{ $card['label'] }}</p>
        <p class="font-headline-md text-headline-md font-bold {{ $card['tone'] }}">{{ number_format($card['value']) }}</p>
      </div>
    @endforeach

    <div class="bg-white rounded-xl border border-border shadow-sm p-3 flex flex-col gap-1 min-w-0">
      <p class="font-label-sm text-label-sm text-on-surface-variant">Rata-rata Jam Check-in</p>
      <p class="font-headline-md text-headline-md font-bold text-primary">{{ $summary['avg_check_in'] ?? '—' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-border shadow-sm p-3 flex flex-col gap-1 min-w-0">
      <p class="font-label-sm text-label-sm text-on-surface-variant">Rata-rata Jam Checkout</p>
      <p class="font-headline-md text-headline-md font-bold text-primary">{{ $summary['avg_check_out'] ?? '—' }}</p>
    </div>
  </section>

  {{-- Historical-classification caveat (Phase 60B radius decision) --}}
  <section class="bg-surface-container-low rounded-xl border border-outline-variant/40 p-unit-md flex items-start gap-2">
    <span class="local-icon text-outline text-[18px] mt-0.5" aria-hidden="true">?</span>
    <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">
      Klasifikasi radius diambil dari keputusan yang tersimpan saat check-in, bukan dihitung ulang dengan radius kantor
      saat ini — sehingga data lama tidak berubah bila radius kantor diubah. Record lama yang tidak menyimpan jarak
      ditandai <span class="font-semibold">Tidak diketahui</span>, bukan diasumsikan berada dalam radius.
    </p>
  </section>

  {{-- Chart 1: attendance trend per date --}}
  @php $trendMax = collect($trend)->max('total') ?: 0; @endphp
  <section class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
    <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">Tren Kehadiran per Tanggal</h3>

    @if(count($trend) === 0)
      <p class="font-body-md text-body-md text-on-surface-variant">Tidak ada data kehadiran pada periode ini.</p>
    @else
      <div class="overflow-x-auto">
        <div class="flex items-end gap-2 h-40 min-w-max pt-2">
          @foreach($trend as $point)
            @php $heightPercent = $trendMax > 0 ? max(4, (int) round($point['total'] / $trendMax * 100)) : 4; @endphp
            <div class="flex flex-col items-center justify-end gap-1 w-8 h-full">
              <span class="font-label-sm text-label-sm text-on-surface">{{ $point['total'] }}</span>
              <div class="w-full bg-primary rounded-t" style="height: {{ $heightPercent }}%"></div>
              <span class="font-label-sm text-[9px] text-on-surface-variant whitespace-nowrap">
                {{ \Illuminate\Support\Carbon::parse($point['date'])->format('d/m') }}
              </span>
            </div>
          @endforeach
        </div>
      </div>
      {{-- Text companion so the information is never colour/graph-only --}}
      <ul class="flex flex-col gap-0.5">
        @foreach($trend as $point)
          <li class="font-label-sm text-label-sm text-on-surface-variant">
            {{ \Illuminate\Support\Carbon::parse($point['date'])->format('d/m/Y') }}: {{ $point['total'] }} presensi
          </li>
        @endforeach
      </ul>
    @endif
  </section>

  {{-- Charts 2–4: distributions, all rendered by the same safe bar partial --}}
  @foreach([
    ['title' => 'Distribusi Status Persetujuan', 'data' => $charts['status'],   'bar' => 'bg-primary'],
    ['title' => 'Dalam Radius vs Luar Radius',   'data' => $charts['radius'],   'bar' => 'bg-secondary'],
    ['title' => 'Kelengkapan Checkout',          'data' => $charts['checkout'], 'bar' => 'bg-success'],
  ] as $chart)
    @php $chartTotal = collect($chart['data'])->sum('value'); @endphp
    <section class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
      <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">{{ $chart['title'] }}</h3>

      @if($chartTotal === 0)
        <p class="font-body-md text-body-md text-on-surface-variant">Tidak ada data kehadiran pada periode ini.</p>
      @endif

      {{-- Categories are always listed, including zero-value ones, so a chart
           with an empty category still renders correctly. --}}
      @foreach($chart['data'] as $slice)
        @php $widthPercent = $chartTotal > 0 ? (int) round($slice['value'] / $chartTotal * 100) : 0; @endphp
        <div class="flex flex-col gap-1">
          <div class="flex justify-between items-baseline gap-2">
            <span class="font-body-md text-body-md text-on-surface">{{ $slice['label'] }}</span>
            <span class="font-label-md text-label-md text-on-surface-variant whitespace-nowrap">
              {{ number_format($slice['value']) }} ({{ $widthPercent }}%)
            </span>
          </div>
          <div class="w-full h-2.5 bg-surface-container-high rounded-full overflow-hidden">
            <div class="h-full {{ $chart['bar'] }} rounded-full" style="width: {{ $widthPercent }}%"></div>
          </div>
        </div>
      @endforeach
    </section>
  @endforeach

  {{-- Detail table --}}
  <section class="bg-white rounded-xl border border-border shadow-sm p-4 flex flex-col gap-3">
    <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wide">Detail Kehadiran</h3>

    @if($records->total() === 0)
      <div class="py-8 text-center flex flex-col items-center gap-2">
        <span class="local-icon text-[40px] text-on-surface-variant" aria-hidden="true">⌕</span>
        <p class="font-body-md text-body-md text-on-surface-variant">
          Tidak ada data kehadiran yang cocok dengan filter ini.
        </p>
      </div>
    @else
      {{-- Horizontal scroll is confined to this wrapper; the page body itself
           never widens (body has overflow-x-hidden). --}}
      <div class="overflow-x-auto -mx-4 px-4">
        <table class="min-w-[1400px] text-left border-collapse">
          <thead>
            <tr class="border-b border-border">
              @foreach([
                'Tanggal','Pegawai','Departemen','Posisi','Check-in','Checkout','Status','Radius',
                'Jarak (m)','Akurasi Masuk (m)','Akurasi Pulang (m)','Alasan Luar Radius',
                'Rencana Kerja','Hasil Pekerjaan','Penyetuju','Waktu Persetujuan','Catatan Persetujuan',
              ] as $heading)
                <th class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wide py-2 pr-4 whitespace-nowrap">{{ $heading }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach($records as $record)
              <tr class="border-b border-border/60 align-top">
                <td class="py-2 pr-4 font-body-md text-body-md whitespace-nowrap">{{ $record->attendance_date?->format('d/m/Y') ?? '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md">{{ $record->employee?->user?->name ?? '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md">{{ $record->employee?->department?->name ?? '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md">{{ $record->employee?->position?->name ?? '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md whitespace-nowrap">{{ $record->check_in_time?->format('H:i') ?? '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md whitespace-nowrap">{{ $record->check_out_time?->format('H:i') ?? '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md whitespace-nowrap">{{ $service->statusLabel($record->status) }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md whitespace-nowrap">{{ $service->radiusLabel($record) }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md whitespace-nowrap">{{ $record->distance_from_office !== null ? number_format((float) $record->distance_from_office, 2) : '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md whitespace-nowrap">{{ $record->check_in_accuracy !== null ? number_format((float) $record->check_in_accuracy, 2) : '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md whitespace-nowrap">{{ $record->check_out_accuracy !== null ? number_format((float) $record->check_out_accuracy, 2) : '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md max-w-[220px]">
                  <x-long-text :value="$record->out_of_radius_reason" />
                </td>
                <td class="py-2 pr-4 font-body-md text-body-md max-w-[220px]">
                  <x-long-text :value="$record->check_in_work_plan" />
                </td>
                <td class="py-2 pr-4 font-body-md text-body-md max-w-[220px]">
                  <x-long-text :value="$record->check_out_work_result" />
                </td>
                <td class="py-2 pr-4 font-body-md text-body-md">{{ $record->approver?->name ?? '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md whitespace-nowrap">{{ $record->approved_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="py-2 pr-4 font-body-md text-body-md max-w-[220px]">
                  <x-long-text :value="$record->approval_note" />
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <p class="font-label-sm text-label-sm text-on-surface-variant">
        Halaman {{ $records->currentPage() }} dari {{ $records->lastPage() }} · {{ number_format($records->total()) }} data
      </p>

      {{-- Pagination keeps the active filters via withQueryString() on the paginator --}}
      @if($records->hasPages())
        <div class="flex justify-between items-center gap-unit-sm">
          @if($records->onFirstPage())
            <span class="flex-1 text-center py-2.5 rounded-lg bg-surface-container-high text-on-surface-variant font-label-md text-label-md opacity-50">Sebelumnya</span>
          @else
            <a href="{{ $records->previousPageUrl() }}" class="flex-1 text-center py-2.5 rounded-lg bg-surface-container-high text-on-surface font-label-md text-label-md">Sebelumnya</a>
          @endif

          @if($records->hasMorePages())
            <a href="{{ $records->nextPageUrl() }}" class="flex-1 text-center py-2.5 rounded-lg bg-primary text-white font-label-md text-label-md">Berikutnya</a>
          @else
            <span class="flex-1 text-center py-2.5 rounded-lg bg-primary/40 text-white font-label-md text-label-md opacity-50">Berikutnya</span>
          @endif
        </div>
      @endif
    @endif
  </section>

</main>

<x-audit-bottom-nav />

</body></html>
