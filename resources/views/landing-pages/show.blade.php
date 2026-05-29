<x-layouts.app
    :title="$landingPage->seoTitle()"
    :meta-description="$landingPage->meta_description"
    :canonical-url="$landingPage->canonicalUrl()"
    :robots="$landingPage->noindex ? 'noindex, nofollow' : null"
    :og-image="$landingPage->ogImageUrl()"
>
    <section class="brand-shell py-10 lg:py-14">
        <div class="max-w-4xl">
            <p class="brand-kicker">Kinderentertainers.nl</p>
            <h1 class="brand-heading mt-3 text-4xl sm:text-5xl">{{ $landingPage->title }}</h1>
            @if ($landingPage->intro)
                <p class="mt-5 max-w-3xl text-lg leading-8 text-slate-700 dark:text-slate-300">{{ $landingPage->intro }}</p>
            @endif
            @if ($landingPage->cta_label && $landingPage->safeCtaUrl())
                <a href="{{ $landingPage->safeCtaUrl() }}" class="brand-button mt-8">{{ $landingPage->cta_label }}</a>
            @endif
        </div>

        @if ($landingPage->body)
            <article class="brand-card mt-10 max-w-4xl p-6">
                <div class="landing-content">{!! $landingPage->bodyHtml() !!}</div>
            </article>
        @endif
    </section>
</x-layouts.app>
