@props([
    'markOnly' => false,
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <svg class="h-11 w-11 shrink-0 drop-shadow-sm" viewBox="0 0 64 64" role="img" aria-label="Kinderentertainers.nl logo" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="brand-logo-purple" x1="15" x2="51" y1="12" y2="54" gradientUnits="userSpaceOnUse">
                <stop stop-color="#8C49F5"/>
                <stop offset="1" stop-color="#4B1FA8"/>
            </linearGradient>
        </defs>
        <rect x="6" y="6" width="52" height="52" rx="16" fill="url(#brand-logo-purple)"/>
        <path d="M13.5 41.5C21.2 50 42.8 50 50.5 41.5" fill="none" stroke="#FFFFFF" stroke-width="4.5" stroke-linecap="round"/>
        <path d="M19 39L26 31L32 43L39 25L45 39" fill="none" stroke="#FFFFFF" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="24" cy="28" r="4.1" fill="#FFFFFF"/>
        <circle cx="40" cy="28" r="4.1" fill="#FFFFFF"/>
        <circle cx="32" cy="18" r="3.7" fill="#FFFFFF"/>
        <path d="M15.5 19.5L17.2 23.2L21 24.9L17.2 26.6L15.5 30.3L13.8 26.6L10 24.9L13.8 23.2L15.5 19.5Z" fill="#FFFFFF"/>
        <path d="M48 12L50 16.4L54.5 18.4L50 20.4L48 24.8L46 20.4L41.5 18.4L46 16.4L48 12Z" fill="#FFFFFF"/>
        <circle cx="51" cy="30" r="1.8" fill="#FFFFFF"/>
    </svg>

    @unless ($markOnly)
        <span class="leading-none">
            <span class="block text-base font-black tracking-tight text-brand-ink dark:text-white sm:text-lg">kinder<span class="text-brand-teal">entertainers</span><span class="text-brand-coral">.nl</span></span>
            <span class="block text-xs font-bold lowercase tracking-[0.12em] text-brand-ink/75 dark:text-white/75">vinden &bull; boeken &bull; genieten</span>
        </span>
    @endunless
</span>
