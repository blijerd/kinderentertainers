<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /klantportaal',
            'Disallow: /setup',
            'Disallow: /login',
            'Disallow: /registreren',
            'Disallow: /email/',
            'Disallow: /wachtwoord-vergeten',
            'Disallow: /wachtwoord-herstellen',
            'Disallow: /webhooks/',
            'Allow: /',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
