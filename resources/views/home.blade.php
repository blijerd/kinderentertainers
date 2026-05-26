<x-layouts.app title="Kinderentertainers.nl">
    <section class="bg-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-20">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">Professioneel boekingsplatform</p>
                <h1 class="mt-4 max-w-3xl text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">Vind snel de juiste kinderentertainer voor elk feest.</h1>
                <p class="mt-5 max-w-2xl text-lg leading-8 text-slate-600">Zoek op skill, regio en beschikbaarheid. Leg daarna direct een aanvraag vast bij de entertainer die past bij jouw evenement.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('entertainers.index') }}" class="rounded-md bg-teal-700 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-800">Bekijk entertainers</a>
                    <a href="{{ route('login') }}" class="rounded-md border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-800 hover:border-teal-500">Entertainer login</a>
                </div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-6">
                <div class="grid gap-4">
                    <div class="rounded-md bg-white p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-950">Zoeken en filteren</p>
                        <p class="mt-1 text-sm text-slate-600">Skills, regio en datum/tijd in een overzicht.</p>
                    </div>
                    <div class="rounded-md bg-white p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-950">Beschikbaarheid</p>
                        <p class="mt-1 text-sm text-slate-600">Beschikbare blokken maken aanvragen concreter.</p>
                    </div>
                    <div class="rounded-md bg-white p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-950">Dashboard</p>
                        <p class="mt-1 text-sm text-slate-600">Entertainers beheren profiel, tarieven en aanvragen zelf.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
