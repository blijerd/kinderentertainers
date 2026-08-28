<?php

namespace Database\Seeders;

use App\Actions\BootstrapPlatform;
use App\Enums\AvailabilityStatus;
use App\Enums\BookingStatus;
use App\Enums\CustomerType;
use App\Models\Availability;
use App\Models\BookingRequest;
use App\Models\Entertainer;
use App\Models\Rate;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $bootstrap = app(BootstrapPlatform::class);
        $bootstrap->seedRolesAndSkills();

        if (app()->isProduction()) {
            return;
        }

        $skillModels = Skill::query()
            ->whereIn('name', BootstrapPlatform::SKILLS)
            ->get()
            ->keyBy('name');

        $admin = User::factory()->create([
            'name' => 'Edwin Rasser',
            'email' => 'admin@kinderentertainers.nl',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        $customer = User::factory()->create([
            'name' => 'Marieke Jansen',
            'email' => 'klant@kinderentertainers.nl',
            'password' => Hash::make('password'),
        ]);
        $customer->assignRole('klant');

        $demoEntertainers = [
            ['name' => 'Sanne Schminkster', 'city' => 'Amsterdam', 'region' => 'Noord-Holland', 'skills' => ['Schminker', 'Glittertattoo artiest']],
            ['name' => 'DJ Daan Kids', 'city' => 'Rotterdam', 'region' => 'Zuid-Holland', 'skills' => ['Kinder-DJ', 'Spelletjesbegeleider']],
            ['name' => 'Magische Milan', 'city' => 'Utrecht', 'region' => 'Utrecht', 'skills' => ['Goochelaar', 'Ballonartiest']],
        ];

        foreach ($demoEntertainers as $index => $demo) {
            $user = User::factory()->create([
                'name' => $demo['name'],
                'email' => 'entertainer'.($index + 1).'@kinderentertainers.nl',
                'password' => Hash::make('password'),
            ]);
            $user->assignRole('entertainer');

            $entertainer = Entertainer::factory()->create([
                'user_id' => $user->id,
                'name' => $demo['name'],
                'slug' => Str::slug($demo['name']),
                'city' => $demo['city'],
                'region' => $demo['region'],
                'featured' => $index === 0,
            ]);

            $entertainer->skills()->sync(
                collect($demo['skills'])->map(fn (string $skill) => $skillModels[$skill]->id)
            );

            foreach (CustomerType::cases() as $type) {
                Rate::factory()->create([
                    'entertainer_id' => $entertainer->id,
                    'customer_type' => $type,
                ]);
            }

            for ($week = 1; $week <= 4; $week++) {
                Availability::factory()->create([
                    'entertainer_id' => $entertainer->id,
                    'date' => now()->addWeeks($week)->next('saturday')->toDateString(),
                    'start_time' => '10:00',
                    'end_time' => '17:00',
                    'status' => AvailabilityStatus::Available,
                ]);
            }

            BookingRequest::factory()->count(2)->create([
                'entertainer_id' => $entertainer->id,
                'customer_id' => $index === 0 ? $customer->id : null,
                'email' => $index === 0 ? $customer->email : fake()->safeEmail(),
                'status' => BookingStatus::New,
                'desired_skills' => $demo['skills'],
                'customer_message' => $index === 0 ? 'We hebben je aanvraag ontvangen en nemen contact met je op.' : null,
            ]);
        }
    }
}
