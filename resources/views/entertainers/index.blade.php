<x-layouts.app title="Kinderentertainers zoeken">
    <section class="brand-shell py-10 lg:py-12">
        <div class="mb-8 max-w-3xl">
            <p class="brand-kicker">Zoeken en boeken</p>
            <h1 class="brand-heading mt-2 text-4xl">Kinderentertainers zoeken</h1>
            <p class="mt-3 text-lg leading-8 text-slate-700 dark:text-slate-300">Filter op skill, leeftijd, type feest, prijs, regio, beoordeling, beschikbaarheid en taal om passende entertainers te vinden.</p>
        </div>
        <livewire:entertainer-index />
        <x-related-brands variant="cards" inset />
    </section>
</x-layouts.app>
