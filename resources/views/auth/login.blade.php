<x-layouts.app title="Inloggen">
    <section class="mx-auto max-w-md px-4 py-14 sm:px-6 lg:px-8">
        <h1 class="brand-heading text-3xl">Inloggen</h1>
        @if (session('status'))
            <p class="mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ session('status') }}</p>
        @endif
        <form method="POST" action="{{ route('login') }}" class="brand-card mt-6 space-y-4 p-5">
            @csrf
            <label class="block space-y-1">
                <span class="text-sm font-medium">E-mail</span>
                <input name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full rounded-md border-slate-300 text-sm">
            </label>
            <label class="block space-y-1">
                <span class="text-sm font-medium">Wachtwoord</span>
                <input name="password" type="password" required class="w-full rounded-md border-slate-300 text-sm">
            </label>
            @error('email') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            <button class="brand-button w-full">Inloggen</button>
        </form>
    </section>
</x-layouts.app>
