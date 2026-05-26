<x-layouts.app title="Aanvraag doen">
    <section class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8">
            <p class="text-sm font-semibold text-teal-700">{{ $entertainer->name }}</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-950">Boekingsaanvraag</h1>
        </div>
        <livewire:booking-request-form :entertainer="$entertainer" />
    </section>
</x-layouts.app>
