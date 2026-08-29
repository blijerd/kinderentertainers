<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeploymentConfigTest extends TestCase
{
    public function test_production_healthcheck_uses_app_url_host_and_long_start_period(): void
    {
        $dockerfile = (string) file_get_contents(base_path('Dockerfile'));
        $healthcheck = (string) file_get_contents(base_path('docker/healthcheck.sh'));
        $entrypoint = (string) file_get_contents(base_path('docker/php/entrypoint.sh'));

        $this->assertStringContainsString('HEALTHCHECK --interval=10s --timeout=5s --start-period=600s --retries=3', $dockerfile);
        $this->assertStringContainsString('CMD ["web"]', $dockerfile);
        $this->assertStringContainsString('Host: ${host}', $healthcheck);
        $this->assertStringContainsString('http://127.0.0.1/up', $healthcheck);
        $this->assertStringContainsString('storage/framework/views/${build_ref}', $entrypoint);
        $this->assertStringContainsString('chown www-data:www-data "storage/framework/views/${build_ref}"', $entrypoint);
        $this->assertStringContainsString('sync_vite_build_asset_cache.sh', $entrypoint);
        $this->assertStringContainsString('artisan migrate --force', $entrypoint);
        $this->assertStringContainsString('artisan content:sync', $entrypoint);
        $this->assertStringContainsString('SYNC_CONTENT_ON_BOOT', $entrypoint);
        $this->assertStringContainsString('exec_php_as_www_data artisan queue:work', $entrypoint);
        $this->assertStringContainsString('exec_php_as_www_data artisan schedule:work', $entrypoint);
    }

    public function test_nginx_serves_vite_assets_from_shared_cache_first(): void
    {
        $nginx = (string) file_get_contents(base_path('docker/nginx/default.conf'));

        $this->assertStringContainsString('storage/app/vite-build-cache', $nginx);
        $this->assertStringContainsString('@vite_build_assets_image', $nginx);
        $this->assertStringContainsString('absolute_redirect off', $nginx);
        $this->assertStringContainsString('rewrite ^ /index.php last', $nginx);
    }

    public function test_compiled_views_are_isolated_per_build_generation(): void
    {
        $this->assertStringContainsString(
            'kinderentertainers_compiled_views_path',
            (string) file_get_contents(base_path('config/view.php')),
        );
        $this->assertStringContainsString(
            'storage/framework/views/{build-ref}/',
            (string) file_get_contents(base_path('docs/coolify-deployment.md')),
        );
    }

    public function test_github_production_deploy_requires_confirm_and_restricted_ssh(): void
    {
        $workflow = (string) file_get_contents(base_path('.github/workflows/deploy-production.yml'));
        $helper = (string) file_get_contents(base_path('scripts/deploy/coolify_github_deploy.sh'));

        $this->assertStringContainsString('confirm:', $workflow);
        $this->assertStringContainsString('DEPLOY', $workflow);
        $this->assertStringContainsString('[deploy]', $workflow);
        $this->assertStringContainsString('PRODUCTION_DEPLOY_SSH_PRIVATE_KEY', $workflow);
        $this->assertStringContainsString('kinderentertainers-coolify-github-deploy.sh', $workflow);
        $this->assertStringContainsString('server.blijevent.nl', $workflow);
        $this->assertStringContainsString('only the \'deploy\' command is allowed', $helper);
        $this->assertStringContainsString('prune_stale_ke_productie_containers.sh', $helper);
    }

    public function test_web_supervisor_does_not_run_queue_or_scheduler(): void
    {
        $supervisor = (string) file_get_contents(base_path('docker/supervisor/laravel.conf'));

        $this->assertStringContainsString('[program:php-fpm]', $supervisor);
        $this->assertStringContainsString('[program:nginx]', $supervisor);
        $this->assertStringNotContainsString('[program:queue]', $supervisor);
        $this->assertStringNotContainsString('[program:scheduler]', $supervisor);
        $this->assertStringNotContainsString('queue:work', $supervisor);
        $this->assertStringNotContainsString('schedule:work', $supervisor);
    }
}
