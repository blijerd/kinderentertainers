<?php

namespace Database\Seeders;

use App\Actions\BootstrapPlatform;
use App\Actions\CreateBlogPost;
use App\Actions\CreateBlogTag;
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

        $this->seedDemoBlog($admin);
    }

    protected function seedDemoBlog(User $author): void
    {
        $kinderfeestje = app(CreateBlogTag::class)->handle([
            'name' => 'Kinderfeestje',
            'slug' => 'kinderfeestje',
            'description' => 'Tips voor een soepel kinderfeestje met een professionele act.',
        ]);
        $boeken = app(CreateBlogTag::class)->handle([
            'name' => 'Boeken',
            'slug' => 'boeken',
            'description' => 'Hoe je via Kinderentertainers.nl een entertainer aanvraagt en boekt.',
        ]);
        $schminken = app(CreateBlogTag::class)->handle([
            'name' => 'Schminken',
            'slug' => 'schminken',
            'description' => 'Alles over schminkartiesten op kinderfeestjes.',
        ]);

        app(CreateBlogPost::class)->handle([
            'author_id' => $author->id,
            'title' => 'Hoe boek je een kinderentertainer?',
            'slug' => 'hoe-boek-je-een-kinderentertainer',
            'intro' => 'Van filteren op skill tot een concrete aanvraag: zo regel je een kinderentertainer zonder gedoe.',
            'body' => <<<'MD'
## Kies de act die past

Filter op skill, regio en beschikbaarheid. Zo blijft een schminker, goochelaar of kinder-DJ over die écht past bij het feest.

## Stuur een concrete aanvraag

Vermeld datum, tijden, locatie en het type feest. Hoe duidelijker de aanvraag, hoe sneller je een passende reactie krijgt.

## Vergelijk en ga akkoord

Bekijk beschikbare matches of een offerte, controleer de voorwaarden en bevestig. De entertainer factureert daarna zelf.
MD,
            'seo_title' => 'Kinderentertainer boeken: zo werkt het',
            'meta_description' => 'Leer hoe je via Kinderentertainers.nl een kinderentertainer zoekt, aanvraagt en boekt.',
            'is_published' => true,
            'published_at' => now()->subDays(3),
            'tag_ids' => [$kinderfeestje->id, $boeken->id],
        ]);

        app(CreateBlogPost::class)->handle([
            'author_id' => $author->id,
            'title' => 'Schminken op een kinderfeestje: waar let je op?',
            'slug' => 'schminken-op-een-kinderfeestje',
            'intro' => 'Een schminkartiest maakt een kinderfeest extra feestelijk. Let op tijdsduur, leeftijd en hypoallergene producten.',
            'body' => <<<'MD'
## Plan genoeg tijd

Reken per kind enkele minuten. Voor een klas of grotere groep is een langere act of een extra artiest prettig.

## Geef de leeftijd door

Peuters hebben andere ontwerpen nodig dan schoolkinderen. Zet de leeftijdsgroep in de aanvraag, dan kan de schminker voorbereiden.

## Vraag naar producten

Professionele schminkers werken met huidvriendelijke producten. Heb je kinderen met een gevoelige huid? Vermeld dat in de aanvraag.
MD,
            'seo_title' => 'Schminken kinderfeestje: praktische tips',
            'meta_description' => 'Tips voor het boeken van een schminkartiest op een kinderfeestje, van tijdsduur tot leeftijd.',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'tag_ids' => [$kinderfeestje->id, $schminken->id],
        ]);
    }
}
