{{--
    Halaman sementara (maintenance placeholder) untuk seluruh akses Payslip.
    Modul Payslip asli (/payslip/detail) belum memiliki desain/implementasi
    final dan sedang menunggu integrasi dari pihak lain. Controller/model/
    migration Payslip yang sudah ada TIDAK dihapus/diubah — halaman ini hanya
    mengganti tampilan pada satu route statis ini. HTTP 200 (bukan 404) agar
    tidak mengganggu healthcheck/monitoring dan tidak terlihat seperti route
    yang rusak.
--}}
@extends('layouts.mobile')

@section('title', 'Slip Gaji - HRIS Mobile App')

@push('head')
<script src="https://cdn.tailwindcss.com"></script>
<style>
    @keyframes payslip-float {
        0%, 100% { transform: translateY(0); }
        50%      { transform: translateY(-8px); }
    }
    @keyframes payslip-gear-spin {
        from { transform: rotate(0deg); }
        to   { transform: rotate(360deg); }
    }
    @keyframes payslip-gear-spin-reverse {
        from { transform: rotate(360deg); }
        to   { transform: rotate(0deg); }
    }
    @keyframes payslip-dot-bounce {
        0%, 80%, 100% { opacity: 0.3; transform: translateY(0); }
        40%           { opacity: 1; transform: translateY(-4px); }
    }
    @keyframes payslip-shimmer {
        0%   { background-position: -80px 0; }
        100% { background-position: 80px 0; }
    }
    @keyframes payslip-page-enter {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .payslip-maintenance-float { animation: payslip-float 3s ease-in-out infinite; }
    .payslip-maintenance-gear  { animation: payslip-gear-spin 6s linear infinite; transform-origin: center; }
    .payslip-maintenance-gear-2 { animation: payslip-gear-spin-reverse 8s linear infinite; transform-origin: center; }
    .payslip-maintenance-dot   { animation: payslip-dot-bounce 1.4s ease-in-out infinite; }
    .payslip-maintenance-dot:nth-child(2) { animation-delay: 0.2s; }
    .payslip-maintenance-dot:nth-child(3) { animation-delay: 0.4s; }
    .payslip-maintenance-badge {
        position: relative;
        overflow: hidden;
    }
    .payslip-maintenance-badge::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.75), transparent);
        background-size: 40px 100%;
        background-repeat: no-repeat;
        animation: payslip-shimmer 2.2s ease-in-out infinite;
    }
    .payslip-maintenance-page { animation: payslip-page-enter 0.5s ease-out both; }

    @media (prefers-reduced-motion: reduce) {
        .payslip-maintenance-float,
        .payslip-maintenance-gear,
        .payslip-maintenance-gear-2,
        .payslip-maintenance-dot,
        .payslip-maintenance-badge::after,
        .payslip-maintenance-page {
            animation: none !important;
        }
    }
</style>
@endpush

@section('content')
<div class="payslip-maintenance-page min-h-screen bg-gray-50 max-w-[390px] mx-auto">
    <header class="bg-white border-b border-gray-200 px-4 h-14 flex items-center gap-3">
        <a href="/employee/dashboard" class="p-2 -ml-2 text-gray-600 hover:text-gray-900 rounded-full hover:bg-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="font-semibold text-gray-900">Slip Gaji</h1>
        <span class="payslip-maintenance-badge ml-auto text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-700 px-2 py-1 rounded-full">Segera Hadir</span>
    </header>

    <main class="flex flex-col items-center justify-center px-6 py-16 text-center">
        <div class="relative w-32 h-32 flex items-center justify-center mb-2">
            <span class="payslip-maintenance-gear absolute -top-1 -right-1 text-indigo-200" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.07-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.61-.22l-2.39.96a7.03 7.03 0 0 0-1.62-.94l-.36-2.54a.5.5 0 0 0-.5-.42h-3.84a.5.5 0 0 0-.5.42l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96a.5.5 0 0 0-.61.22L2.75 8.84a.5.5 0 0 0 .12.64l2.03 1.58c-.05.3-.08.62-.08.94s.02.64.07.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32c.14.24.42.32.61.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.28.28.42.5.42h3.84c.22 0 .45-.14.5-.42l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.24.1.48.02.61-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58ZM12 15.5A3.5 3.5 0 1 1 15.5 12 3.5 3.5 0 0 1 12 15.5Z"/></svg>
            </span>
            <span class="payslip-maintenance-gear-2 absolute -bottom-1 -left-1 text-indigo-100" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.07-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.61-.22l-2.39.96a7.03 7.03 0 0 0-1.62-.94l-.36-2.54a.5.5 0 0 0-.5-.42h-3.84a.5.5 0 0 0-.5.42l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96a.5.5 0 0 0-.61.22L2.75 8.84a.5.5 0 0 0 .12.64l2.03 1.58c-.05.3-.08.62-.08.94s.02.64.07.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32c.14.24.42.32.61.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.28.28.42.5.42h3.84c.22 0 .45-.14.5-.42l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.24.1.48.02.61-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58ZM12 15.5A3.5 3.5 0 1 1 15.5 12 3.5 3.5 0 0 1 12 15.5Z"/></svg>
            </span>
            <div class="payslip-maintenance-float w-20 h-20 rounded-2xl bg-white shadow-md border border-gray-100 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
        </div>

        <p class="text-5xl font-extrabold text-gray-200 tracking-tight leading-none mb-3" aria-hidden="true">404</p>

        <h2 class="text-lg font-bold text-gray-800 mb-2">Fitur Slip Gaji Sedang Disiapkan</h2>
        <p class="text-sm text-gray-500 max-w-xs leading-relaxed">
            Halaman slip gaji sedang dalam tahap pengembangan. Fitur ini akan tersedia setelah proses integrasi selesai.
        </p>

        <div class="flex items-center gap-1.5 mt-4" aria-hidden="true">
            <span class="payslip-maintenance-dot w-2 h-2 rounded-full bg-indigo-400"></span>
            <span class="payslip-maintenance-dot w-2 h-2 rounded-full bg-indigo-400"></span>
            <span class="payslip-maintenance-dot w-2 h-2 rounded-full bg-indigo-400"></span>
        </div>

        <div class="mt-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg px-4 py-3 max-w-xs flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
            <span>Data penggajian Anda tetap aman.</span>
        </div>

        <a href="/employee/dashboard"
           class="mt-8 inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-colors active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Kembali ke Beranda
        </a>
    </main>
</div>
@endsection
