<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden — HRIS Mobile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-md p-8 text-center">
        <div class="text-6xl font-bold text-red-500 mb-2">403</div>
        <h1 class="text-xl font-semibold text-slate-800 mb-2">Access Forbidden</h1>
        <p class="text-slate-500 text-sm mb-6">
            You don't have permission to access this page.
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
                Back to Dashboard
            </a>
        @else
            <a href="{{ route('login') }}"
               class="inline-block w-full py-3 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition">
                Go to Login
            </a>
        @endauth
    </div>
</body>
</html>
