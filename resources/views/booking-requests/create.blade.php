<x-layouts.app title="Aanvraag doen">
    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8">
            <p class="brand-kicker">{{ $entertainer?->name ?? 'Algemene aanvraag' }}</p>
            <h1 class="brand-heading mt-2 text-3xl">Boekingsaanvraag</h1>
        </div>
        <livewire:booking-request-form :entertainer="$entertainer" :skills="$skills" />
    </section>
</x-layouts.app>
