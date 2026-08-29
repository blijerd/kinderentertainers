<?php

namespace Tests\Feature;

use App\Actions\ResolveApplicationBuildReferenceAction;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResolveApplicationBuildReferenceActionTest extends TestCase
{
    public function test_configured_build_ref_wins_over_file_and_clock(): void
    {
        config([
            'app.build_ref' => '26331411',
            'app.build_ref_path' => $this->writeBuildRefFile('99010100'),
            'app.build_ref_timezone' => 'Europe/Amsterdam',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-01-01 00:00:00', 'Europe/Amsterdam'));

        $this->assertSame('26331411', app(ResolveApplicationBuildReferenceAction::class)->handle());
    }

    public function test_build_ref_file_is_used_when_env_is_empty(): void
    {
        config([
            'app.build_ref' => '',
            'app.build_ref_path' => $this->writeBuildRefFile('26331209'),
            'app.build_ref_timezone' => 'Europe/Amsterdam',
        ]);

        $this->assertSame('26331209', app(ResolveApplicationBuildReferenceAction::class)->handle());
    }

    public function test_format_is_year_iso_week_day_hour_in_amsterdam(): void
    {
        config([
            'app.build_ref' => '',
            'app.build_ref_path' => sys_get_temp_dir().'/missing-ke-build-ref',
            'app.build_ref_timezone' => 'Europe/Amsterdam',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-14 11:31:00', 'Europe/Amsterdam'));

        $this->assertSame('26331411.001', app(ResolveApplicationBuildReferenceAction::class)->handle());
    }

    public function test_hour_sequence_suffix_is_accepted(): void
    {
        config([
            'app.build_ref' => '26331412.03',
            'app.build_ref_path' => sys_get_temp_dir().'/missing-ke-build-ref',
        ]);

        $this->assertSame('26331412.03', app(ResolveApplicationBuildReferenceAction::class)->handle());
    }

    public function test_display_label_uses_second_digit_of_year_week_day_and_full_hour(): void
    {
        $action = app(ResolveApplicationBuildReferenceAction::class);

        $this->assertSame('63422078', $action->displayLabel('26331422.78'));
        $this->assertSame('63422001', $action->displayLabel('26331422.1'));
        $this->assertSame('63422010', $action->displayLabel('26331422.10'));
        $this->assertSame('63422111', $action->displayLabel('26331422.111'));
        $this->assertSame('63422999', $action->displayLabel('26331422.999'));
        $this->assertSame('63422001', $action->displayLabel('26331422.1000'));
        $this->assertSame('63409001', $action->displayLabel('26331409.1'));
        $this->assertSame('63411001', $action->displayLabel('26331411'));
        $this->assertSame('not-a-stamp', $action->displayLabel('not-a-stamp'));
        $this->assertSame(1, $action->wrapSequence(0));
        $this->assertSame(1, $action->wrapSequence(1000));
        $this->assertSame(1, $action->wrapSequence(1999));
        $this->assertSame(999, $action->wrapSequence(999));
    }

    public function test_invalid_configured_values_are_ignored(): void
    {
        config([
            'app.build_ref' => 'not a stamp',
            'app.build_ref_path' => $this->writeBuildRefFile('26331411'),
        ]);

        $this->assertSame('26331411', app(ResolveApplicationBuildReferenceAction::class)->handle());
    }

    public function test_footer_partial_receives_build_ref_via_view_composer(): void
    {
        $this->assertStringNotContainsString(
            'ResolveApplicationBuildReferenceAction',
            (string) file_get_contents(resource_path('views/partials/footer-build-ref.blade.php')),
        );
        $this->assertStringNotContainsString(
            'ResolveApplicationBuildReferenceAction',
            (string) file_get_contents(resource_path('views/components/layouts/app.blade.php')),
        );
    }

    protected function writeBuildRefFile(string $value): string
    {
        $path = sys_get_temp_dir().'/ke-build-ref-'.bin2hex(random_bytes(6));
        file_put_contents($path, $value."\n");

        $this->beforeApplicationDestroyed(static function () use ($path): void {
            if (is_file($path)) {
                unlink($path);
            }
        });

        return $path;
    }
}
