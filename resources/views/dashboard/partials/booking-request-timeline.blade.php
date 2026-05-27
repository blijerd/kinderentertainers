@php($canAddEvent = $canAddEvent ?? false)

<div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-3">
    <h3 class="text-sm font-bold text-brand-ink">Tijdlijn</h3>
    <div class="mt-3 space-y-3">
        @forelse ($bookingRequest->events as $event)
            <div class="border-l-2 border-teal-200 pl-3">
                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span class="font-semibold text-slate-700">{{ $event->type->label() }}</span>
                    <span>{{ $event->created_at->format('d-m-Y H:i') }}</span>
                    @if ($event->actor_name)
                        <span>{{ $event->actor_name }}</span>
                    @endif
                </div>
                @if ($event->type === \App\Enums\BookingRequestEventType::StatusChange)
                    <p class="mt-1 text-sm text-slate-700">
                        {{ $event->old_status?->label() ?? 'Onbekend' }} naar {{ $event->new_status?->label() ?? 'Onbekend' }}
                    </p>
                @elseif ($event->body)
                    <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $event->body }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-600">Nog geen logregels.</p>
        @endforelse
    </div>

    @if ($canAddEvent)
        <form method="POST" action="{{ route('dashboard.booking-requests.events.store', $bookingRequest) }}" class="mt-4 grid gap-2">
            @csrf
            <select name="type" class="rounded-md border-slate-300 text-sm" aria-label="Type logregel">
                <option value="{{ \App\Enums\BookingRequestEventType::EntertainerResponse->value }}">Entertainer-reactie</option>
                <option value="{{ \App\Enums\BookingRequestEventType::InternalNote->value }}">Interne notitie</option>
            </select>
            <textarea name="body" rows="3" class="rounded-md border-slate-300 text-sm" placeholder="Nieuwe logregel">{{ old('body') }}</textarea>
            <button class="rounded-md border border-teal-200 px-3 py-2 text-sm font-bold text-teal-900">Logregel toevoegen</button>
        </form>
    @endif
</div>
