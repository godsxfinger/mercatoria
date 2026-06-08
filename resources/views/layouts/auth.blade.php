<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" type="image/png" href="{{ asset('images/mercatoria.png') }}">
    <link href="{{ asset('css/auth.css') }}" rel="stylesheet">
</head>
<body id="top" class="@yield('body_class')">
    <div class="auth-shell">
        <main class="auth-main">
            @include('components.alerts')
            @yield('content')
        </main>
    </div>
</body>
</html>
