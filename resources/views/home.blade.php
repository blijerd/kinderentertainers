<x-layouts.app title="Kinderentertainers.nl">
    <section class="overflow-hidden">
        <div class="brand-shell grid gap-10 py-14 lg:grid-cols-[1.05fr_0.95fr] lg:py-20">
            <div class="flex flex-col justify-center">
                <p class="brand-kicker">Professioneel boekingsplatform</p>
                <h1 class="brand-heading mt-4 max-w-3xl text-4xl sm:text-5xl">Een feestelijke match zonder regelwerk.</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-700">Vind entertainers voor kinderfeestjes, scholen en events. Filter op act, regio en beschikbaarheid en stuur direct een concrete aanvraag.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('entertainers.index') }}" class="brand-button">Bekijk entertainers</a>
                    <a href="{{ route('login') }}" class="brand-button-secondary">Entertainer login</a>
                </div>
                <div class="mt-10 grid max-w-2xl gap-3 text-sm font-semibold text-brand-ink sm:grid-cols-3">
                    <div class="rounded-lg bg-white/75 p-4 shadow-sm shadow-teal-900/5">Act, regio en datum in een overzicht</div>
                    <div class="rounded-lg bg-white/75 p-4 shadow-sm shadow-teal-900/5">Direct aanvragen bij de juiste entertainer</div>
                    <div class="rounded-lg bg-white/75 p-4 shadow-sm shadow-teal-900/5">Dashboard voor profiel en boekingen</div>
                </div>
            </div>
            <div class="brand-panel p-4 sm:p-6">
                <div class="rounded-lg bg-brand-teal p-5 text-white">
                    <div class="flex items-center justify-between gap-4">
                        <x-brand.logo mark-only class="rounded-md bg-white/10 p-2" />
                        <span class="rounded-full bg-white/18 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-brand-mint">live match</span>
                    </div>
                    <p class="mt-12 text-sm font-bold uppercase tracking-[0.18em] text-brand-mint">Voor zaterdag 14:00</p>
                    <h2 class="mt-2 text-3xl font-black tracking-tight">Goochelaar, schmink of ballonartiest?</h2>
                    <p class="mt-3 max-w-md text-sm leading-6 text-teal-50">Maak snel duidelijk wie beschikbaar is, welke act past en wat de aanvraag concreet maakt.</p>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-amber-100 bg-amber-50 p-4">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-amber-700">Populair</p>
                        <p class="mt-2 font-bold text-brand-ink">Ballonnenclown</p>
                        <p class="mt-1 text-sm text-slate-600">Vanaf EUR 125</p>
                    </div>
                    <div class="rounded-lg border border-rose-100 bg-rose-50 p-4">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-coral">Aanvraag</p>
                        <p class="mt-2 font-bold text-brand-ink">20 kinderen</p>
                        <p class="mt-1 text-sm text-slate-600">Utrecht, 2 uur</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
