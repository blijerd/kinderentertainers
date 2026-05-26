<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\AvailabilityStatus;
use App\Enums\CustomerType;
use App\Models\Availability;
use App\Models\BookingRequest;
use App\Models\Entertainer;
use App\Models\Rate;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EntertainerDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $entertainer = $this->currentEntertainer($request);

        $entertainer->load(['skills', 'rates', 'availabilities' => fn ($query) => $query->upcoming()->orderBy('date')]);
        $bookingRequests = $entertainer->bookingRequests()->latest()->paginate(10);
        $skills = Skill::where('active', true)->orderBy('name')->get();

        return view('dashboard.index', compact('entertainer', 'bookingRequests', 'skills'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('update', $entertainer);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_introduction' => ['required', 'string', 'max:240'],
            'bio' => ['nullable', 'string', 'max:10000'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'working_radius_km' => ['required', 'integer', 'min:1', 'max:500'],
        ]);

        $entertainer->update($validated);

        return back()->with('status', 'Profiel bijgewerkt.');
    }

    public function updateSkills(Request $request): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('update', $entertainer);

        $validated = $request->validate([
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', Rule::exists('skills', 'id')->where('active', true)],
        ]);

        $entertainer->skills()->sync($validated['skills'] ?? []);

        return back()->with('status', 'Skills bijgewerkt.');
    }

    public function storeAvailability(Request $request): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('create', Availability::class);

        $validated = $this->validateAvailability($request);
        $entertainer->availabilities()->create($validated);

        return back()->with('status', 'Beschikbaarheid toegevoegd.');
    }

    public function updateAvailability(Request $request, Availability $availability): RedirectResponse
    {
        $this->authorize('update', $availability);

        $availability->update($this->validateAvailability($request));

        return back()->with('status', 'Beschikbaarheid bijgewerkt.');
    }

    public function destroyAvailability(Availability $availability): RedirectResponse
    {
        $this->authorize('delete', $availability);

        $availability->delete();

        return back()->with('status', 'Beschikbaarheid verwijderd.');
    }

    public function storeRate(Request $request): RedirectResponse
    {
        $entertainer = $this->currentEntertainer($request);

        $this->authorize('create', Rate::class);

        $validated = $this->validateRate($request, $entertainer);
        $entertainer->rates()->create($validated);

        return back()->with('status', 'Tarief toegevoegd.');
    }

    public function updateRate(Request $request, Rate $rate): RedirectResponse
    {
        $this->authorize('update', $rate);

        $rate->update($this->validateRate($request, $rate->entertainer, $rate));

        return back()->with('status', 'Tarief bijgewerkt.');
    }

    public function destroyRate(Rate $rate): RedirectResponse
    {
        $this->authorize('delete', $rate);

        $rate->delete();

        return back()->with('status', 'Tarief verwijderd.');
    }

    public function updateBookingStatus(Request $request, BookingRequest $bookingRequest): RedirectResponse
    {
        $this->authorize('update', $bookingRequest);

        $validated = $request->validate([
            'status' => ['required', 'in:optie,bevestigd,afgewezen'],
        ]);

        $bookingRequest->update(['status' => BookingStatus::from($validated['status'])]);

        return back()->with('status', 'Aanvraagstatus bijgewerkt.');
    }

    private function currentEntertainer(Request $request): Entertainer
    {
        $entertainer = $request->user()->entertainer;

        abort_unless($entertainer, 403);

        return $entertainer;
    }

    private function validateAvailability(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::enum(AvailabilityStatus::class)],
            'internal_note' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'date' => 'datum',
            'start_time' => 'starttijd',
            'end_time' => 'eindtijd',
            'internal_note' => 'interne notitie',
        ]);
    }

    private function validateRate(Request $request, Entertainer $entertainer, ?Rate $rate = null): array
    {
        $validated = $request->validate([
            'customer_type' => [
                'required',
                Rule::enum(CustomerType::class),
                Rule::unique('rates', 'customer_type')
                    ->where('entertainer_id', $entertainer->id)
                    ->ignore($rate),
            ],
            'starting_rate_cents' => ['required', 'integer', 'min:0'],
            'hourly_rate_cents' => ['required', 'integer', 'min:0'],
            'minimum_hours' => ['required', 'numeric', 'min:0.5', 'max:24'],
            'travel_cost_cents_per_km' => ['required', 'integer', 'min:0'],
            'vat_included' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'customer_type' => 'doelgroep',
            'starting_rate_cents' => 'starttarief',
            'hourly_rate_cents' => 'uurtarief',
            'minimum_hours' => 'minimum aantal uren',
            'travel_cost_cents_per_km' => 'reiskosten per kilometer',
            'vat_included' => 'btw inclusief',
        ]);

        $validated['vat_included'] = $request->boolean('vat_included');

        return $validated;
    }
}
