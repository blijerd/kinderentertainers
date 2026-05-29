<x-layouts.app title="E-mail bevestigen">
    <section class="mx-auto max-w-md px-4 py-14 sm:px-6 lg:px-8">
        <h1 class="brand-heading text-3xl">E-mail bevestigen</h1>
        @if (session('status'))
            <p class="mt-4 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">{{ session('status') }}</p>
        @endif
        <div class="brand-card mt-6 space-y-4 p-5">
            <p class="text-sm text-slate-700">We hebben een verificatielink naar je e-mailadres gestuurd.</p>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="brand-button w-full">Nieuwe link sturen</button>
            </form>
        </div>
    </section>
</x-layouts.app>
