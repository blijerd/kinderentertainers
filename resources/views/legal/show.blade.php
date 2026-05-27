<x-layouts.app :title="$type->label()">
    <section class="brand-shell py-10">
        <div class="max-w-3xl">
            <p class="brand-kicker">Juridisch</p>
            <h1 class="brand-heading mt-3 text-4xl">{{ $type->label() }}</h1>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
                Versie {{ $version->version_label }} · gepubliceerd op {{ $version->published_at->format('d-m-Y') }}
            </p>
        </div>

        <article class="legal-content mt-8 max-w-3xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            {!! $bodyHtml !!}
        </article>
    </section>
</x-layouts.app>
