<x-layouts.app
    :title="$tag->seoTitle()"
    :meta-description="$tag->metaDescriptionText()"
    :canonical-url="$canonicalUrl"
    :robots="$tag->noindex ? 'noindex, nofollow' : null"
>
    <x-slot:head>
        <link rel="alternate" type="application/rss+xml" title="Blog — Kinderentertainers.nl" href="{{ route('blog.feed') }}">
        <x-json-ld :schema="$tag->jsonLd()" />
        <x-json-ld :schema="$tag->breadcrumbJsonLd()" />
        @if ($posts->previousPageUrl())
            <link rel="prev" href="{{ $posts->previousPageUrl() }}">
        @endif
        @if ($posts->nextPageUrl())
            <link rel="next" href="{{ $posts->nextPageUrl() }}">
        @endif
    </x-slot:head>

    <section class="brand-shell py-10 lg:py-14">
        <div class="max-w-3xl">
            <p class="brand-kicker"><a href="{{ route('blog.index') }}" class="hover:text-brand-coral">Blog</a> · tag</p>
            <h1 class="brand-heading mt-3 text-4xl sm:text-5xl">{{ $tag->name }}</h1>
            @if ($tag->description)
                <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-700 dark:text-slate-300">{{ $tag->description }}</p>
            @else
                <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-700 dark:text-slate-300">Artikelen over {{ $tag->name }}.</p>
            @endif
        </div>

        @include('blog.partials.post-list', ['posts' => $posts])
        <x-related-brands variant="cards" inset />
    </section>
</x-layouts.app>
