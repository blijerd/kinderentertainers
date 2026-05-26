<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Kinderentertainers.nl' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="text-lg font-bold text-slate-950">Kinderentertainers.nl</a>
            <nav class="flex items-center gap-4 text-sm font-medium text-slate-700">
                <a href="{{ route('entertainers.index') }}" class="hover:text-teal-700">Entertainers</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="hover:text-teal-700">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="hover:text-teal-700">Uitloggen</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-teal-700">Inloggen</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>
</body>
</html>
