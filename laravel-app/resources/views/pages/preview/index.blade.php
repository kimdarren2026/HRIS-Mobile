@extends('layouts.mobile')

@section('title', 'HRIS Mobile App Preview')

@section('body_class', 'min-h-screen bg-slate-100 text-slate-900 antialiased')

@section('content')
<main style="max-width: 390px; margin: 0 auto; min-height: 100vh; padding: 24px 16px; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
    <header style="margin-bottom: 20px;">
        <p style="margin: 0 0 6px; color: #475569; font-size: 13px; font-weight: 700; text-transform: uppercase;">Static Blade Preview</p>
        <h1 style="margin: 0; color: #0f172a; font-size: 26px; line-height: 1.15;">HRIS Mobile App</h1>
    </header>

    <nav style="display: grid; gap: 10px;">
        @foreach ($screens as $screen)
            <a href="{{ $screen['uri'] }}" style="display: block; padding: 14px 16px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff; color: #0f172a; text-decoration: none; font-weight: 700;">
                {{ $screen['label'] }}
            </a>
        @endforeach
    </nav>
</main>
@endsection
