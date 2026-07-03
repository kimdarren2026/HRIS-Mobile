<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Hadir')</title>
    @stack('head')
</head>
<body class="@yield('body_class', 'min-h-screen bg-slate-100 text-slate-900 antialiased')">
    @yield('content')
    @stack('scripts')
</body>
</html>
