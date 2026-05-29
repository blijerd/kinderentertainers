<x-layouts.app title="Registreren">
    <section class="brand-shell py-10">
        <div class="mx-auto max-w-xl">
            <div class="brand-card p-6">
                <p class="brand-kicker">Account</p>
                <h1 class="brand-heading mt-2 text-3xl">Registreren</h1>
                <form method="POST" action="{{ route('register') }}" class="mt-6 grid gap-4">
                    @csrf
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Type account</span>
                        <select name="account_type" required class="w-full rounded-md border-slate-300 text-sm">
                            <option value="klant" @selected(old('account_type') === 'klant')>Klant</option>
                            <option value="entertainer" @selected(old('account_type') === 'entertainer')>Entertainer</option>
                        </select>
                        @error('account_type') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Naam</span>
                        <input name="name" value="{{ old('name') }}" required class="w-full rounded-md border-slate-300 text-sm">
                        @error('name') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">E-mail</span>
                        <input name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-md border-slate-300 text-sm">
                        @error('email') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="space-y-1">
                            <span class="text-sm font-medium">Plaats voor entertainer</span>
                            <input name="city" value="{{ old('city') }}" class="w-full rounded-md border-slate-300 text-sm">
                            @error('city') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                        </label>
                        <label class="space-y-1">
                            <span class="text-sm font-medium">Regio voor entertainer</span>
                            <input name="region" value="{{ old('region') }}" class="w-full rounded-md border-slate-300 text-sm">
                            @error('region') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                        </label>
                    </div>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Wachtwoord</span>
                        <input name="password" type="password" required class="w-full rounded-md border-slate-300 text-sm">
                        @error('password') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Wachtwoord bevestigen</span>
                        <input name="password_confirmation" type="password" required class="w-full rounded-md border-slate-300 text-sm">
                    </label>
                    <button class="brand-button justify-center">Account aanmaken</button>
                </form>
            </div>
        </div>
    </section>
</x-layouts.app>
