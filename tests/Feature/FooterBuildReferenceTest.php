<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterBuildReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_footer_shows_configured_build_reference_next_to_copyright(): void
    {
        config(['app.build_ref' => '26331411']);

        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('footer-build-ref', $html);
        $this->assertStringContainsString('Build 🙂 63411001', $html);
        $this->assertStringNotContainsString('Build 🙂 26331411', $html);
        $this->assertStringContainsString('Buildreferentie 26331411', $html);
        $this->assertStringContainsString('&copy; Blijevent B.V. '.now()->year, $html);
    }

    public function test_footer_shortens_year_week_day_hour_and_keeps_sequence(): void
    {
        config(['app.build_ref' => '26331422.78']);

        $html = $this->get('/')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Build 🙂 63422078', $html);
        $this->assertStringContainsString('Buildreferentie 26331422.78', $html);
        $this->assertStringNotContainsString('Build 🙂 26331422.78', $html);
        $this->assertStringNotContainsString('6342.78', $html);
    }

    public function test_docker_image_stamps_bootstrap_build_ref(): void
    {
        $dockerfile = (string) file_get_contents(base_path('Dockerfile'));

        $this->assertStringContainsString('bootstrap/build-ref', $dockerfile);
        $this->assertStringContainsString('build_ref="${stamp}.${seq}"', $dockerfile);
        $this->assertStringContainsString('ARG KE_BUILD_REF', $dockerfile);
        $this->assertStringContainsString('TZ=Europe/Amsterdam date +%y%V%d%H', $dockerfile);
        $this->assertStringContainsString('--start-period=600s', $dockerfile);
        $this->assertStringContainsString('kinderentertainers-healthcheck', $dockerfile);
    }
}
