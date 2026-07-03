<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 Tidak Ditemukan — HRIS Mobile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-md p-4 sm:p-8 text-center">
        <div id="error-state-anim" class="w-[380px] h-[380px] max-w-[80vw] max-h-[80vw] mx-auto" aria-hidden="true"></div>
        <div class="text-sm font-semibold text-slate-400 tracking-wider mb-2">ERROR 404</div>
        <h1 class="text-xl font-semibold text-slate-800 mb-2">Halaman Tidak Ditemukan</h1>
        <p class="text-slate-500 text-sm mb-6">
            Halaman yang Anda cari tidak ada atau telah dipindahkan.
        </p>
        @auth
            @php
                $dashboard = match(auth()->user()->role) {
                    'admin_hr', 'super_admin' => '/admin/dashboard',
                    'finance'                 => '/finance/dashboard',
                    default                   => '/employee/dashboard',
                };
            @endphp
            <a href="{{ $dashboard }}"
               class="inline-block w-full py-3 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition">
                Kembali ke Dasbor
            </a>
        @else
            <a href="{{ route('login') }}"
               class="inline-block w-full py-3 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition">
                Ke Halaman Masuk
            </a>
        @endauth
    </div>
    <script src="/assets/lottie/vendor/lottie-web.min.js"></script>
    <script src="/assets/lottie/lottie-helper.js"></script>
    <script>
        mountLottie('error-state-anim', '/assets/lottie/error-state.json', { loop: false, autoplay: true });
    </script>
</body>
</html>
