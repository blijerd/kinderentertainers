<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Actielijst
        </x-slot>

        <x-slot name="description">
            De signalen zijn bedoeld om dagelijks te prioriteren wat handmatig aandacht nodig heeft.
        </x-slot>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($signals as $signal)
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $signal['label'] }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $signal['description'] }}</p>
                        </div>

                        <x-filament::badge :color="$signal['color']">
                            {{ $signal['count'] }}
                        </x-filament::badge>
                    </div>

                    <div class="mt-4">
                        <x-filament::link :href="$signal['url']" size="sm">
                            {{ $signal['action'] }}
                        </x-filament::link>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Populaire skills zonder genoeg beschikbaar aanbod</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Skills met minimaal 2 recente of aankomende aanvragen en minder dan 2 actieve entertainers.</p>
                </div>

                <x-filament::link :href="$skillsUrl" size="sm">
                    Skills beheren
                </x-filament::link>
            </div>

            @if ($underSuppliedSkills->isEmpty())
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Geen aanbodtekort gevonden op basis van de huidige aanvragen.</p>
            @else
                <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($underSuppliedSkills as $skill)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $skill->name }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $skill->demand_count }} aanvragen, {{ $skill->active_entertainers_count }} actieve entertainers
                                </p>
                            </div>

                            <x-filament::badge color="info">
                                Aanbodtekort
                            </x-filament::badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
