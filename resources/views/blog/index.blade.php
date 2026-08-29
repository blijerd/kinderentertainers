<x-layouts.app
    title="Blog — Kinderentertainers.nl"
    meta-description="Tips en achtergrond over kinderentertainers, kinderfeestjes en boeken via Kinderentertainers.nl."
    :canonical-url="$canonicalUrl"
>
    <x-slot:head>
        <link rel="alternate" type="application/rss+xml" title="Blog — Kinderentertainers.nl" href="{{ route('blog.feed') }}">
        <x-json-ld :schema="$blogSchema" />
        <x-json-ld :schema="$breadcrumbSchema" />
        @if ($posts->previousPageUrl())
            <link rel="prev" href="{{ $posts->previousPageUrl() }}">
        @endif
        @if ($posts->nextPageUrl())
            <link rel="next" href="{{ $posts->nextPageUrl() }}">
        @endif
    </x-slot:head>

    <section class="brand-shell py-10 lg:py-14">
        <div class="max-w-3xl">
            <p class="brand-kicker">Inspiratie en tips</p>
            <h1 class="brand-heading mt-3 text-4xl sm:text-5xl">Blog</h1>
            <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-700 dark:text-slate-300">Artikelen over kinderentertainers, kinderfeestjes en hoe je via het platform een passende act boekt.</p>
        </div>

        @if ($tags->isNotEmpty())
            <ul class="mt-8 flex flex-wrap gap-2">
                @foreach ($tags as $tag)
                    <li>
                        <a href="{{ route('blog.tag', $tag) }}" class="rounded-full border border-slate-200 px-3 py-1 text-sm font-bold text-slate-700 hover:border-brand-teal hover:text-brand-teal dark:border-slate-700 dark:text-slate-200">{{ $tag->name }}</a>
                    </li>
                @endforeach
            </ul>
        @endif

        @include('blog.partials.post-list', ['posts' => $posts])
        <x-related-brands variant="cards" inset />
    </section>
</x-layouts.app>
