<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<title>Hadir! — Campus HRIS</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; }

    /* Animated soft gradient background */
    @keyframes bg-shift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    .bg-wellness {
        background: linear-gradient(135deg, #ede9fe, #e0f2fe, #f0fdf4, #faf5ff);
        background-size: 300% 300%;
        animation: bg-shift 12s ease-in-out infinite;
    }

    /* Floating orbs */
    @keyframes float-slow {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-14px) scale(1.04); }
    }
    @keyframes float-med {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-9px); }
    }
    .orb-1 { animation: float-slow 7s ease-in-out infinite; }
    .orb-2 { animation: float-med 5s ease-in-out infinite 1.5s; }
    .orb-3 { animation: float-slow 9s ease-in-out infinite 3s; }

    /* Heartbeat line */
    @keyframes hb-draw {
        0%   { stroke-dashoffset: 500; opacity: 0.2; }
        40%  { opacity: 0.9; }
        100% { stroke-dashoffset: -500; opacity: 0.2; }
    }
    .hb-path {
        stroke-dasharray: 500;
        stroke-dashoffset: 500;
        animation: hb-draw 3.2s cubic-bezier(.4,0,.6,1) infinite;
    }

    /* Input focus ring */
    .w-input:focus {
        outline: none;
        border-color: #7c3aed;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(124,58,237,0.12);
    }

    /* Primary button glow */
    @keyframes glow-pulse {
        0%, 100% { box-shadow: 0 4px 18px rgba(109,40,217,0.35); }
        50%       { box-shadow: 0 4px 28px rgba(109,40,217,0.55); }
    }
    .btn-login { animation: glow-pulse 2.6s ease-in-out infinite; }
    .btn-login:hover { animation: none; box-shadow: 0 6px 24px rgba(109,40,217,0.5); }
    .btn-login:active { transform: scale(0.98); }
</style>
</head>
<body class="flex items-center justify-center min-h-screen bg-wellness">

<!-- Background decorative orbs -->
<div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
    <div class="orb-1 absolute -top-24 -left-24 w-72 h-72 rounded-full bg-violet-200/40 blur-3xl"></div>
    <div class="orb-2 absolute top-1/3 -right-20 w-56 h-56 rounded-full bg-sky-200/50 blur-2xl"></div>
    <div class="orb-3 absolute -bottom-16 left-1/3 w-48 h-48 rounded-full bg-cyan-100/60 blur-2xl"></div>
</div>

<!-- Phone card -->
<div class="w-full max-w-[390px] min-h-screen md:min-h-0 md:h-[844px] md:rounded-[2rem] md:shadow-2xl
            bg-white/75 backdrop-blur-md flex flex-col relative overflow-hidden
            ring-1 ring-violet-100 mx-auto my-0 md:my-8">

    <!-- Heartbeat decoration strip -->
    <div class="absolute top-0 left-0 w-full pointer-events-none" style="height:72px;" aria-hidden="true">
        <svg width="100%" height="72" viewBox="0 0 390 72" preserveAspectRatio="none"
             fill="none" xmlns="http://www.w3.org/2000/svg">
            <path class="hb-path"
                  d="M0,36 L55,36 L70,18 L85,54 L100,8 L115,64 L130,36 L160,36
                     L200,36 L240,36 L255,22 L270,50 L285,12 L300,60 L315,36 L390,36"
                  stroke="#a78bfa" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>

    <!-- Logo / hero -->
    <div class="flex flex-col items-center justify-center pt-20 pb-6 px-8">
        <div class="mb-3 select-none" aria-label="Hadir!">
            <span style="font-size:52px;font-weight:800;letter-spacing:-2px;color:#0f172a;line-height:1;">Hadir</span><span style="font-size:52px;font-weight:800;color:#06b6d4;line-height:1;">!</span>
        </div>
        <p style="font-size:14px;font-weight:500;color:#64748b;letter-spacing:0.02em;">Jangan Lupa Absen Ya!</p>
    </div>

    <!-- Form -->
    <div class="flex-1 px-8 pt-2 flex flex-col gap-4 w-full">
        <form action="{{ route('login.attempt') }}" method="POST" class="flex flex-col gap-4 w-full" novalidate>
            @csrf

            {{-- Validation errors --}}
            @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600" role="alert">
                {{ $errors->first() }}
            </div>
            @endif

            {{-- Session status flash (e.g. after logout) --}}
            @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status">
                {{ session('status') }}
            </div>
            @endif

            <!-- Email -->
            <div class="flex flex-col gap-1.5">
                <label for="email"
                       style="font-size:11px;font-weight:700;color:#475569;letter-spacing:0.06em;text-transform:uppercase;">
                    Alamat Email
                </label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none"
                         style="color:#94a3b8;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                         aria-hidden="true">
                        <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <input id="email" name="email" type="email"
                           class="w-input block w-full pl-9 pr-4 py-3 border border-slate-200 rounded-xl bg-slate-50
                                  text-sm text-slate-800 placeholder-slate-400 transition-all"
                           placeholder="nama@kampus.ac.id"
                           value="{{ old('email') }}"
                           required autocomplete="email">
                </div>
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-1.5">
                <label for="password"
                       style="font-size:11px;font-weight:700;color:#475569;letter-spacing:0.06em;text-transform:uppercase;">
                    Kata Sandi
                </label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none"
                         style="color:#94a3b8;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                         aria-hidden="true">
                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <input id="password" name="password" type="password"
                           class="w-input block w-full pl-9 pr-10 py-3 border border-slate-200 rounded-xl bg-slate-50
                                  text-sm text-slate-800 placeholder-slate-400 transition-all"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                    <button type="button" onclick="togglePassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 transition-colors"
                            style="color:#94a3b8;" aria-label="Tampilkan / sembunyikan password">
                        <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <div class="flex justify-end mt-0.5">
                    {{-- TODO Phase 5: add forgot-password route --}}
                    <a href="#" class="text-xs font-medium transition-colors"
                       style="color:#7c3aed;">Lupa Kata Sandi?</a>
                </div>
            </div>

            <!-- Login button -->
            <div class="pt-2">
                <button type="submit"
                        class="btn-login w-full py-3.5 px-4 rounded-xl font-semibold text-sm text-white transition-all"
                        style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
                    Masuk
                </button>
            </div>

            <!-- Divider -->
            <div class="flex items-center gap-3">
                <div class="flex-1 h-px" style="background:#e2e8f0;"></div>
                <span style="font-size:11px;color:#94a3b8;font-weight:500;">atau</span>
                <div class="flex-1 h-px" style="background:#e2e8f0;"></div>
            </div>

            <!-- Google Workspace button -->
            <a href="{{ route('auth.google.redirect') }}"
                    class="w-full flex items-center justify-center gap-3 py-3 px-4 rounded-xl border
                           text-sm font-medium select-none transition-colors"
                    style="border-color:#e2e8f0;background:#fff;color:#334155;">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                     aria-hidden="true">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Masuk dengan Google Workspace
            </a>
        </form>
    </div>

    <!-- Footer -->
    <div class="pb-8 pt-4 px-8 w-full flex justify-center">
        <p style="font-size:11px;color:#94a3b8;">© Campus HRIS 2026</p>
    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eye-icon');
        const hidden = input.type === 'password';
        input.type = hidden ? 'text' : 'password';
        icon.innerHTML = hidden
            ? '<path d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>'
            : '<path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
</script>
</body>
</html>
