<!DOCTYPE html>
<html lang="nl" data-theme="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Kinderentertainers.nl' }}</title>
    <script>
        (() => {
            let prefs = {};
            try {
                prefs = JSON.parse(localStorage.getItem('ke_accessibility') || '{}') || {};
            } catch {
                prefs = {};
            }
            const theme = prefs.theme || 'auto';
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.dataset.theme = theme;
            document.documentElement.classList.toggle('dark', theme === 'dark' || (theme === 'auto' && prefersDark));
        })();
    </script>
    <link rel="icon" href="{{ asset('brand/favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans text-slate-900 antialiased dark:text-slate-100">
    <header class="sticky top-0 z-20 border-b border-violet-100/80 bg-white/92 backdrop-blur dark:border-slate-700 dark:bg-slate-950/90">
        <div class="brand-shell flex items-center justify-between py-3">
            <a href="{{ route('home') }}" class="rounded-md focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-teal">
                <x-brand.logo />
            </a>
            <nav class="flex items-center gap-3 text-sm font-bold text-slate-700 dark:text-slate-200 sm:gap-5">
                <a href="{{ route('entertainers.index') }}" class="hover:text-brand-coral">Entertainers</a>
                @auth
                    @if (auth()->user()->hasRole('klant') && ! auth()->user()->hasRole('entertainer'))
                        <a href="{{ route('customer-portal.index') }}" class="hover:text-brand-coral">Klantportaal</a>
                    @else
                        <a href="{{ route('dashboard') }}" class="hover:text-brand-coral">Dashboard</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="hover:text-brand-coral">Uitloggen</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-brand-coral">Inloggen</a>
                @endauth
                <button type="button" data-accessibility-open class="rounded-md border border-violet-200 px-2 py-1 text-xs hover:border-brand-coral dark:border-slate-600" aria-label="Weergave aanpassen">Aa</button>
            </nav>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-slate-200 py-8 text-sm dark:border-slate-800">
        <div class="brand-shell flex flex-wrap gap-4 text-slate-600 dark:text-slate-300">
            <a href="{{ route('legal.terms') }}" class="hover:text-brand-coral">Algemene voorwaarden</a>
            <a href="{{ route('legal.privacy') }}" class="hover:text-brand-coral">Privacyverklaring</a>
            <a href="{{ route('legal.cookies') }}" class="hover:text-brand-coral">Cookieverklaring</a>
            <button type="button" data-cookie-open class="hover:text-brand-coral">Cookievoorkeuren</button>
        </div>
    </footer>

    <div data-cookie-modal class="preference-modal hidden" role="dialog" aria-modal="true" aria-labelledby="cookie-modal-title">
        <div class="preference-modal__panel">
            <h2 id="cookie-modal-title" class="text-xl font-bold text-brand-ink dark:text-white">Cookievoorkeuren</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">We bewaren noodzakelijke cookies altijd. Analytics en marketing gebruiken we alleen als je dat toestaat.</p>
            <div class="mt-5 space-y-3">
                <label class="flex items-center justify-between gap-4 rounded-md border border-slate-200 p-3 dark:border-slate-700">
                    <span><strong>Noodzakelijk</strong><span class="block text-xs text-slate-500 dark:text-slate-400">Sessies, beveiliging en voorkeuren.</span></span>
                    <input type="checkbox" checked disabled class="rounded border-slate-300">
                </label>
                <label class="flex items-center justify-between gap-4 rounded-md border border-slate-200 p-3 dark:border-slate-700">
                    <span><strong>Analytics</strong><span class="block text-xs text-slate-500 dark:text-slate-400">Helpt de site te verbeteren.</span></span>
                    <input type="checkbox" data-cookie-category="analytics" class="rounded border-slate-300">
                </label>
                <label class="flex items-center justify-between gap-4 rounded-md border border-slate-200 p-3 dark:border-slate-700">
                    <span><strong>Marketing</strong><span class="block text-xs text-slate-500 dark:text-slate-400">Externe media en campagnes.</span></span>
                    <input type="checkbox" data-cookie-category="marketing" class="rounded border-slate-300">
                </label>
            </div>
            <div class="mt-5 grid gap-2 sm:grid-cols-3">
                <button type="button" data-cookie-necessary class="brand-button-secondary px-3 py-2">Alleen nodig</button>
                <button type="button" data-cookie-all class="brand-button-secondary px-3 py-2">Alles accepteren</button>
                <button type="button" data-cookie-save class="brand-button px-3 py-2">Opslaan</button>
            </div>
        </div>
    </div>

    <div data-accessibility-modal class="preference-modal hidden" role="dialog" aria-modal="true" aria-labelledby="accessibility-modal-title">
        <div class="preference-modal__panel">
            <h2 id="accessibility-modal-title" class="text-xl font-bold text-brand-ink dark:text-white">Toegankelijkheid</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Kies hoe de site wordt weergegeven.</p>
            <fieldset class="mt-5">
                <legend class="text-sm font-bold">Kleurenschema</legend>
                <div class="mt-3 grid grid-cols-3 gap-2">
                    <label class="preference-choice"><input type="radio" name="theme" value="auto"> Auto</label>
                    <label class="preference-choice"><input type="radio" name="theme" value="light"> Licht</label>
                    <label class="preference-choice"><input type="radio" name="theme" value="dark"> Donker</label>
                </div>
            </fieldset>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" data-accessibility-close class="brand-button-secondary px-3 py-2">Sluiten</button>
                <button type="button" data-accessibility-save class="brand-button px-3 py-2">Opslaan</button>
            </div>
        </div>
    </div>
</body>
</html>
