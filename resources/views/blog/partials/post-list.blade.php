@props(['posts'])

@if ($posts->isEmpty())
    <p class="mt-8 text-slate-700 dark:text-slate-300">Er zijn nog geen artikelen in deze lijst.</p>
@else
    <div class="mt-8 grid gap-6">
        @foreach ($posts as $post)
            <article class="brand-card p-6">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-teal">
                    {{ ($post->published_at ?? $post->created_at)?->isoFormat('D MMMM YYYY') }}
                </p>
                <h2 class="brand-heading mt-2 text-2xl">
                    <a href="{{ route('blog.show', $post) }}" class="hover:text-brand-teal">{{ $post->title }}</a>
                </h2>
                @if ($post->intro || $post->metaDescriptionText())
                    <p class="mt-3 text-slate-700 dark:text-slate-300">{{ $post->intro ?: $post->metaDescriptionText() }}</p>
                @endif
                @if ($post->tags->isNotEmpty())
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($post->tags as $tag)
                            <li>
                                <a href="{{ route('blog.tag', $tag) }}" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 hover:bg-brand-mint dark:bg-slate-800 dark:text-slate-200">{{ $tag->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('blog.show', $post) }}" class="mt-5 inline-flex font-semibold text-brand-teal hover:text-brand-coral">Lees verder</a>
            </article>
        @endforeach
    </div>
@endif

@if ($posts->hasPages())
    <nav class="mt-10 flex flex-wrap items-center justify-between gap-3 text-sm" aria-label="Paginering">
        @if ($posts->onFirstPage())
            <span class="text-slate-400">Vorige</span>
        @else
            <a href="{{ $posts->previousPageUrl() }}" rel="prev" class="brand-button-secondary px-3 py-2">Vorige</a>
        @endif
        <span class="text-slate-600 dark:text-slate-300">Pagina {{ $posts->currentPage() }} van {{ $posts->lastPage() }}</span>
        @if ($posts->hasMorePages())
            <a href="{{ $posts->nextPageUrl() }}" rel="next" class="brand-button-secondary px-3 py-2">Volgende</a>
        @else
            <span class="text-slate-400">Volgende</span>
        @endif
    </nav>
@endif
