<?php

namespace App\Actions;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class BootstrapPlatform
{
    /**
     * @var list<string>
     */
    public const SKILLS = [
        'Schminker',
        'Ballonartiest',
        'Kinder-DJ',
        'Goochelaar',
        'Glittertattoo artiest',
        'Clown',
        'Spelletjesbegeleider',
        'Mascotte',
        'Poppenkast',
        'Workshop begeleider',
    ];

    /**
     * @var list<string>
     */
    public const ROLES = ['admin', 'entertainer', 'klant'];

    public function seedRolesAndSkills(): void
    {
        foreach (self::ROLES as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        foreach (self::SKILLS as $name) {
            Skill::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => "Professionele skill: {$name}.",
                    'active' => true,
                ],
            );
        }
    }

    public function createFirstAdmin(string $name, string $email, string $password): User
    {
        if (User::query()->exists()) {
            throw new RuntimeException('Er bestaat al een gebruiker. Setup is al uitgevoerd.');
        }

        $this->seedRolesAndSkills();

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->assignRole('admin');

        return $user;
    }
}
