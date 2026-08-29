<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts(at: static function (): array {
            $hosts = [];

            foreach ([
                (string) config('app.url', env('APP_URL', '')),
                (string) config('app.asset_url', env('ASSET_URL', '')),
            ] as $url) {
                $host = parse_url(trim($url), PHP_URL_HOST);

                if (! is_string($host) || $host === '') {
                    continue;
                }

                $hosts[] = $host;

                if (str_starts_with($host, 'www.')) {
                    $hosts[] = substr($host, 4);
                }
            }

            return array_values(array_unique($hosts)) ?: ['localhost', '127.0.0.1'];
        }, subdomains: true);

        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'webhooks/betalingen/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            'setup_token',
            'token',
            'api_token',
            'api_key',
            'client_secret',
            'secret_key',
            'webhook_secret',
            'refresh_token',
            'server_token',
        ]);
    })->create();

$app->usePublicPath(dirname(__DIR__).'/public_html');

return $app;
