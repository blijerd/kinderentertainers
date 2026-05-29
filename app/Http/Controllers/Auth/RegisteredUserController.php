<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountingProvider;
use App\Enums\IntegrationProvider;
use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Models\Entertainer;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_type' => ['required', 'in:klant,entertainer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'city' => ['nullable', 'required_if:account_type,entertainer', 'string', 'max:255'],
            'region' => ['nullable', 'required_if:account_type,entertainer', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
        ]);

        Role::findOrCreate($validated['account_type'], 'web');

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        $user->assignRole($validated['account_type']);
        event(new Registered($user));

        if ($validated['account_type'] === 'entertainer') {
            $entertainer = Entertainer::query()->create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name']),
                'short_introduction' => 'Nieuw profiel in onboarding.',
                'city' => $validated['city'],
                'region' => $validated['region'],
                'working_radius_km' => 40,
                'accounting_provider' => AccountingProvider::Manual,
                'payment_provider' => PaymentProvider::Manual,
                'active' => false,
                'featured' => false,
                'profile_complete' => false,
                'profile_quality_score' => 0,
            ]);

            foreach (IntegrationProvider::cases() as $provider) {
                $entertainer->integrations()->firstOrCreate(['provider' => $provider]);
            }
        }

        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'entertainer';
        $slug = $base;
        $counter = 2;

        while (Entertainer::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
