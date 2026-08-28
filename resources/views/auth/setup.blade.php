<x-layouts.app title="Setup">
    <section class="mx-auto max-w-md px-4 py-14 sm:px-6 lg:px-8">
        <h1 class="brand-heading text-3xl">Eerste gebruiker aanmaken</h1>
        <p class="mt-2 text-sm text-slate-600">Maak het eerste beheeraccount aan om het dashboard te kunnen gebruiken.</p>

        <form method="POST" action="{{ route('setup.store') }}" class="brand-card mt-6 space-y-4 p-5">
            @csrf
@if (! empty($setupToken))
                <input type="hidden" name="token" value="{{ $setupToken }}">
            @endif

            <label class="block space-y-1">
                <span class="text-sm font-medium">Naam</span>
                <input name="name" type="text" value="{{ old('name') }}" required autofocus class="w-full rounded-md border-slate-300 text-sm">
                @error('name') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">E-mail</span>
                <input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-md border-slate-300 text-sm">
                @error('email') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">Wachtwoord</span>
                <input name="password" type="password" required class="w-full rounded-md border-slate-300 text-sm">
                @error('password') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
            </label>

            <label class="block space-y-1">
                <span class="text-sm font-medium">Wachtwoord bevestigen</span>
                <input name="password_confirmation" type="password" required class="w-full rounded-md border-slate-300 text-sm">
            </label>

            <button class="brand-button w-full">Account aanmaken</button>
        </form>
    </section>
</x-layouts.app>
