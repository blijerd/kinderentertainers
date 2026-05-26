<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Kinderentertainers.nl' }}</title>
    <link rel="icon" href="{{ asset('brand/logo-mark.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans text-slate-900 antialiased">
    <header class="sticky top-0 z-20 border-b border-teal-100/80 bg-white/92 backdrop-blur">
        <div class="brand-shell flex items-center justify-between py-3">
            <a href="{{ route('home') }}" class="rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-teal">
                <x-brand.logo />
            </a>
            <nav class="flex items-center gap-3 text-sm font-bold text-slate-700 sm:gap-5">
                <a href="{{ route('entertainers.index') }}" class="hover:text-brand-coral">Entertainers</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="hover:text-brand-coral">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="hover:text-brand-coral">Uitloggen</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-brand-coral">Inloggen</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>
</body>
</html>
