<x-layouts.app title="Aanvraag ontvangen">
    <section class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6 lg:px-8">
        <x-brand.logo mark-only class="mx-auto justify-center" />
        <h1 class="brand-heading mt-6 text-3xl">Bedankt voor je aanvraag</h1>
        <p class="mt-4 text-slate-700">We hebben je aanvraag ontvangen. De volgende stap hangt af van je aanvraagtype.</p>
        <div class="mt-8 grid gap-4 text-left md:grid-cols-2">
            <div class="rounded-lg border border-teal-100 bg-white p-5">
                <h2 class="font-bold text-brand-ink">Specifieke entertainer</h2>
                <p class="mt-2 text-sm leading-6 text-slate-700">De entertainer beoordeelt je aanvraag en stuurt daarna een reactie of offerte. Je ontvangt de verdere link per e-mail.</p>
            </div>
            <div class="rounded-lg border border-amber-100 bg-white p-5">
                <h2 class="font-bold text-brand-ink">Algemene aanvraag</h2>
                <p class="mt-2 text-sm leading-6 text-slate-700">Beschikbare entertainers kunnen reageren. Via de matchlink in je e-mail kies je daarna de entertainer die het beste past.</p>
            </div>
        </div>
        <a href="{{ route('entertainers.index') }}" class="brand-button mt-8">Verder zoeken</a>
    </section>
</x-layouts.app>
