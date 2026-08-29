<x-layouts.app
    :title="$post->seoTitle()"
    :meta-description="$post->metaDescriptionText()"
    :canonical-url="$post->canonicalUrl()"
    :robots="$post->noindex ? 'noindex, nofollow' : null"
    :og-image="$post->ogImageUrl()"
    og-type="article"
>
    <x-slot:head>
        @if ($post->published_at)
            <meta property="article:published_time" content="{{ $post->published_at->toAtomString() }}">
        @endif
        <meta property="article:modified_time" content="{{ $post->updated_at?->toAtomString() }}">
        @foreach ($post->tags as $tag)
            <meta property="article:tag" content="{{ $tag->name }}">
        @endforeach
        <link rel="alternate" type="application/rss+xml" title="Blog — Kinderentertainers.nl" href="{{ route('blog.feed') }}">
        <x-json-ld :schema="$post->jsonLd()" />
        <x-json-ld :schema="$post->breadcrumbJsonLd()" />
    </x-slot:head>

    <section class="brand-shell py-10 lg:py-14">
        <div class="max-w-4xl">
            <p class="brand-kicker">
                <a href="{{ route('blog.index') }}" class="hover:text-brand-coral">Blog</a>
                @if ($post->published_at)
                    · {{ $post->published_at->isoFormat('D MMMM YYYY') }}
                @endif
            </p>
            <h1 class="brand-heading mt-3 text-4xl sm:text-5xl">{{ $post->title }}</h1>
            @if ($post->author?->name)
                <p class="mt-4 text-sm font-semibold text-slate-600 dark:text-slate-300">Door {{ $post->author->name }}</p>
            @endif
            @if ($post->intro)
                <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-700 dark:text-slate-300">{{ $post->intro }}</p>
            @endif
            @if ($post->tags->isNotEmpty())
                <ul class="mt-6 flex flex-wrap gap-2">
                    @foreach ($post->tags as $tag)
                        <li>
                            <a href="{{ route('blog.tag', $tag) }}" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 hover:bg-brand-mint dark:bg-slate-800 dark:text-slate-200">{{ $tag->name }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($post->coverImageUrl())
            <img src="{{ $post->coverImageUrl() }}" alt="" class="mt-10 max-w-4xl rounded-lg" width="1200" height="630">
        @endif

        @if ($post->body)
            <article class="brand-card mt-10 max-w-4xl p-6">
                <div class="blog-content">{!! $post->bodyHtml() !!}</div>
            </article>
        @endif

        @if ($relatedPosts->isNotEmpty())
            <aside class="mt-12 max-w-4xl" aria-labelledby="related-posts-title">
                <h2 id="related-posts-title" class="brand-heading text-2xl">Meer artikelen</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    @foreach ($relatedPosts as $related)
                        <a href="{{ route('blog.show', $related) }}" class="brand-card p-4 hover:border-brand-teal">
                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-teal">{{ ($related->published_at ?? $related->created_at)?->isoFormat('D MMMM YYYY') }}</p>
                            <h3 class="mt-2 font-bold text-brand-ink dark:text-white">{{ $related->title }}</h3>
                        </a>
                    @endforeach
                </div>
            </aside>
        @endif

        <div class="mt-10">
            <a href="{{ route('blog.index') }}" class="font-semibold text-brand-teal hover:text-brand-coral">Terug naar het blogoverzicht</a>
        </div>
        <x-related-brands variant="cards" inset />
    </section>
</x-layouts.app>
