@props([
    'message' => null,
])

@php
    $message = $message ?? session('success');
@endphp

@if ($message)
    <div {{ $attributes->class([
        'mb-unit-md flex items-center gap-2 rounded-lg border border-success/30',
        'bg-success/10 px-4 py-3 font-body-md text-body-md text-success',
    ]) }}>
        <span class="material-symbols-outlined text-[18px]">check_circle</span>
        <span>{{ $message }}</span>
    </div>
@endif
