<x-layouts.app title="Inloggen">
    <section class="mx-auto max-w-md px-4 py-14 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-slate-950">Inloggen</h1>
        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
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
            <button class="w-full rounded-md bg-teal-700 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-800">Inloggen</button>
        </form>
    </section>
</x-layouts.app>
