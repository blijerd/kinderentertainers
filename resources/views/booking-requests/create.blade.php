<x-layouts.app title="Aanvraag doen">
    <section class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 max-w-3xl">
            <p class="brand-kicker">{{ $entertainer?->name ?? 'Algemene aanvraag' }}</p>
            <h1 class="brand-heading mt-2 text-4xl">Boekingsaanvraag</h1>
            <div class="mt-5 grid gap-3 text-sm font-semibold text-slate-700 md:grid-cols-4">
                <div class="brand-step">1. Gegevens invullen</div>
                <div class="brand-step">2. Entertainer reageert</div>
                <div class="brand-step">3. Offerte bekijken</div>
                <div class="brand-step">4. Akkoord geven</div>
            </div>
        </div>
        <livewire:booking-request-form :entertainer="$entertainer" :skills="$skills" />
    </section>
</x-layouts.app>
