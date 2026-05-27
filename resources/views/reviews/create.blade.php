<x-layouts.app :title="'Review voor '.$review->entertainer->name">
    <section class="brand-shell py-10">
        <div class="mx-auto max-w-2xl">
            <div class="brand-card p-6">
                <p class="brand-kicker">Review</p>
                <h1 class="brand-heading mt-2 text-3xl">Hoe was {{ $review->entertainer->name }}?</h1>
                <p class="mt-3 text-slate-700">Deel je ervaring. Na controle kan je review zichtbaar worden op het profiel.</p>

                @if ($review->isSubmitted())
                    <div class="mt-6 rounded-md bg-teal-50 p-4 text-sm font-semibold text-brand-ink">
                        Deze review is al ingestuurd. Bedankt voor je reactie.
                    </div>
                @else
                    <form method="POST" action="{{ route('reviews.store', $review->token) }}" class="mt-6 space-y-5">
                        @csrf

                        <div>
                            <label for="rating" class="block text-sm font-semibold text-brand-ink">Beoordeling</label>
                            <select id="rating" name="rating" class="mt-2 w-full rounded-md border-slate-300" required>
                                <option value="">Kies een beoordeling</option>
                                @for ($rating = 5; $rating >= 1; $rating--)
                                    <option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ $rating }} van 5</option>
                                @endfor
                            </select>
                            @error('rating')
                                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="title" class="block text-sm font-semibold text-brand-ink">Titel</label>
                            <input id="title" name="title" value="{{ old('title') }}" maxlength="120" class="mt-2 w-full rounded-md border-slate-300" placeholder="Bijvoorbeeld: geweldig kinderfeest">
                            @error('title')
                                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="body" class="block text-sm font-semibold text-brand-ink">Review</label>
                            <textarea id="body" name="body" rows="6" class="mt-2 w-full rounded-md border-slate-300" required>{{ old('body') }}</textarea>
                            @error('body')
                                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <button class="brand-button">Review versturen</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
</x-layouts.app>
