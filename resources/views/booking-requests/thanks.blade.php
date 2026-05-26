<x-layouts.app title="Aanvraag ontvangen">
    <section class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <x-brand.logo mark-only class="mx-auto justify-center" />
        <h1 class="brand-heading mt-6 text-3xl">Bedankt voor je aanvraag</h1>
        <p class="mt-4 text-slate-700">De entertainer ontvangt je aanvraag en kan deze verder beoordelen.</p>
        <a href="{{ route('entertainers.index') }}" class="brand-button mt-8">Terug naar overzicht</a>
    </section>
</x-layouts.app>
