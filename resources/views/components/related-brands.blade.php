@props([
    'variant' => 'compact',
    'inset' => false,
])

@php
    $brands = collect($company['related_brands'] ?? [])
        ->filter(fn (array $brand): bool => filled($brand['url'] ?? null) && filled($brand['name'] ?? null));
@endphp

@if ($brands->isNotEmpty())
    @if ($variant === 'cards')
        <section {{ $attributes->class($inset ? 'mt-10' : 'brand-shell py-10 lg:py-12') }} aria-labelledby="related-brands-title">
            <p class="brand-kicker">Ook van Blijevent</p>
            <h2 id="related-brands-title" class="brand-heading mt-2 text-3xl">Kidsdisco en Kids DJ Edwin</h2>
            <p class="mt-3 max-w-2xl text-slate-700 dark:text-slate-300">Voor een complete kinderdisco of een vaste kinder-DJ kun je ook terecht bij deze merken.</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($brands as $brand)
                    <a
                        href="{{ $brand['url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="brand-card flex items-start gap-4 p-5 transition hover:-translate-y-px hover:border-brand-teal"
                    >
                        <img
                            src="{{ asset($brand['logo']) }}"
                            alt=""
                            width="44"
                            height="44"
                            class="h-11 w-11 shrink-0 rounded-lg"
                            decoding="async"
                        >
                        <span>
                            <span class="block font-black text-brand-ink dark:text-white">{{ $brand['name'] }}</span>
                            @if (! empty($brand['tagline']))
                                <span class="mt-1 block text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $brand['tagline'] }}</span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @else
        <nav {{ $attributes }} aria-label="Andere merken van Blijevent">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Ook van Blijevent</p>
            <div class="mt-3 flex flex-wrap gap-3">
                @foreach ($brands as $brand)
                    <a
                        href="{{ $brand['url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 font-bold text-brand-ink transition hover:border-brand-teal hover:text-brand-teal dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    >
                        <img
                            src="{{ asset($brand['logo']) }}"
                            alt=""
                            width="28"
                            height="28"
                            class="h-7 w-7 rounded-md"
                            decoding="async"
                        >
                        <span>{{ $brand['name'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    @endif
@endif
