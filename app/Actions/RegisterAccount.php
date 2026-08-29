<?php

namespace App\Actions;

use App\Enums\AccountingProvider;
use App\Enums\PaymentProvider;
use App\Models\Entertainer;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RegisterAccount
{
    public function __construct(private readonly EnsureDefaultEntertainerIntegrations $ensureDefaultIntegrations) {}

    /**
     * @param  array{account_type: string, name: string, email: string, password: string, city?: string|null, region?: string|null, phone?: string|null}  $data
     */
    public function handle(array $data): User
    {
        Role::findOrCreate($data['account_type'], 'web');

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $user->assignRole($data['account_type']);
        event(new Registered($user));

        if ($data['account_type'] === 'entertainer') {
            $entertainer = Entertainer::query()->create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'short_introduction' => 'Nieuw profiel in onboarding.',
                'city' => $data['city'],
                'region' => $data['region'],
                'working_radius_km' => 40,
                'accounting_provider' => AccountingProvider::Manual,
                'payment_provider' => PaymentProvider::Manual,
                'active' => false,
                'featured' => false,
                'profile_complete' => false,
                'profile_quality_score' => 0,
            ]);

            $this->ensureDefaultIntegrations->handle($entertainer);
        }

        return $user;
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
