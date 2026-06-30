@extends('layouts.mobile')

@section('title', 'Payslip - HRIS Mobile App')

@push('head')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 max-w-[390px] mx-auto">
    <header class="bg-white border-b border-gray-200 px-4 h-14 flex items-center gap-3">
        <a href="/employee/dashboard" class="p-2 -ml-2 text-gray-600 hover:text-gray-900 rounded-full hover:bg-gray-100">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="font-semibold text-gray-900">Payslip</h1>
    </header>

    <main class="flex flex-col items-center justify-center px-6 py-20 text-center">
        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <h2 class="text-base font-semibold text-gray-700 mb-2">No Payslip Data Available</h2>
        <p class="text-sm text-gray-500 max-w-xs leading-relaxed">
            Payslip details will be available here once the external payroll integration is configured.
        </p>
    </main>
</div>
@endsection
