<x-layouts.app title="Wachtwoord herstellen">
    <section class="mx-auto max-w-md px-4 py-14 sm:px-6 lg:px-8">
        <h1 class="brand-heading text-3xl">Wachtwoord herstellen</h1>
        <form method="POST" action="{{ route('password.store') }}" class="brand-card mt-6 space-y-4 p-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="block space-y-1">
                <span class="text-sm font-medium">E-mail</span>
                <input name="email" type="email" value="{{ old('email', $request->email) }}" required autofocus class="w-full rounded-md border-slate-300 text-sm">
            </label>
            <label class="block space-y-1">
                <span class="text-sm font-medium">Nieuw wachtwoord</span>
                <input name="password" type="password" required class="w-full rounded-md border-slate-300 text-sm">
            </label>
            <label class="block space-y-1">
                <span class="text-sm font-medium">Wachtwoord bevestigen</span>
                <input name="password_confirmation" type="password" required class="w-full rounded-md border-slate-300 text-sm">
            </label>
            @error('email') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            @error('password') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            <button class="brand-button w-full">Wachtwoord opslaan</button>
        </form>
    </section>
</x-layouts.app>
