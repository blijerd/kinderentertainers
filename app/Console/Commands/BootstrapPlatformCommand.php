<?php

namespace App\Console\Commands;

use App\Actions\BootstrapPlatform;
use App\Models\User;
use Illuminate\Console\Command;
use RuntimeException;

class BootstrapPlatformCommand extends Command
{
    protected $signature = 'app:bootstrap
                            {--name= : Naam van de eerste beheerder}
                            {--email= : E-mail van de eerste beheerder}
                            {--password= : Wachtwoord van de eerste beheerder}
                            {--skills-only : Alleen rollen en skills aanmaken}';

    protected $description = 'Maak rollen, skills en optioneel de eerste beheerder aan zonder demo-data.';

    public function handle(BootstrapPlatform $bootstrap): int
    {
        $bootstrap->seedRolesAndSkills();
        $this->info('Rollen en skills zijn bijgewerkt.');

        if ($this->option('skills-only')) {
            return self::SUCCESS;
        }

        if (User::query()->exists()) {
            $this->warn('Er bestaat al een gebruiker. Er is geen extra beheerder aangemaakt.');

            return self::SUCCESS;
        }

        $name = $this->option('name') ?: $this->ask('Naam van de beheerder');
        $email = $this->option('email') ?: $this->ask('E-mail van de beheerder');
        $password = $this->option('password') ?: $this->secret('Wachtwoord van de beheerder');

        if (! is_string($name) || $name === '' || ! is_string($email) || $email === '' || ! is_string($password) || strlen($password) < 8) {
            $this->error('Naam, e-mail en een wachtwoord van minimaal 8 tekens zijn verplicht.');

            return self::FAILURE;
        }

        try {
            $user = $bootstrap->createFirstAdmin($name, $email, $password);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Beheerder {$user->email} is aangemaakt. /setup is nu gesloten.");

        return self::SUCCESS;
    }
}
