<?php

namespace App\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;

class ResolveApplicationBuildReferenceAction
{
    /**
     * Compact deploy stamp: YYWWddhh.NNN in Europe/Amsterdam.
     *
     * YY = two-digit year, WW = ISO week, dd = day of month, hh = 24h hour,
     * NNN = sequence within that hour (001-999, then wraps to 001).
     */
    public const FORMAT = 'yWdH';

    public const CLOCK_FALLBACK_SEQUENCE = '001';

    public const DISPLAY_SEQUENCE_MAX = 999;

    private const MAX_LENGTH = 16;

    private const PATTERN = '/^[A-Za-z0-9._-]{4,16}$/';

    private ?string $resolved = null;

    public function handle(?CarbonInterface $now = null): string
    {
        if ($this->resolved !== null && $now === null) {
            return $this->resolved;
        }

        $reference = $this->fromConfiguredValue()
            ?? $this->fromBuildRefFile()
            ?? $this->fromGitCommit()
            ?? $this->format($now ?? now());

        if ($now === null) {
            $this->resolved = $reference;
        }

        return $reference;
    }

    public function format(CarbonInterface $moment): string
    {
        $timezone = (string) config('app.build_ref_timezone', 'Europe/Amsterdam');

        return $moment->copy()->timezone($timezone)->format(self::FORMAT).'.'.self::CLOCK_FALLBACK_SEQUENCE;
    }

    /**
     * Public footer stamp: second digit of YY, WW and dd, the full hour, plus a 3-digit sequence.
     * Example: 26331422.78 → 63422078. Sequence 1000 wraps to 001.
     */
    public function displayLabel(?string $reference = null): string
    {
        $reference ??= $this->handle();

        if (preg_match('/^(\d)(\d)(\d)(\d)(\d)(\d)(\d)(\d)(?:\.(\d+))?$/', $reference, $matches) !== 1) {
            return $reference;
        }

        $shortened = $matches[2].$matches[4].$matches[6].$matches[7].$matches[8];
        $sequence = isset($matches[9]) && $matches[9] !== '' ? (int) $matches[9] : 1;

        return $shortened.sprintf('%03d', $this->wrapSequence($sequence));
    }

    public function wrapSequence(int $sequence): int
    {
        if ($sequence <= 0) {
            return 1;
        }

        return (($sequence - 1) % self::DISPLAY_SEQUENCE_MAX) + 1;
    }

    protected function fromConfiguredValue(): ?string
    {
        return $this->normalize((string) config('app.build_ref', ''));
    }

    protected function fromBuildRefFile(): ?string
    {
        $path = $this->buildRefPath();

        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            return null;
        }

        return $this->normalize((string) file_get_contents($path));
    }

    protected function fromGitCommit(): ?string
    {
        if (app()->runningUnitTests() || ! is_dir(base_path('.git'))) {
            return null;
        }

        try {
            $result = Process::timeout(2)->run(['git', '-C', base_path(), 'log', '-1', '--format=%cI']);
        } catch (\Throwable) {
            return null;
        }

        if (! $result->successful()) {
            return null;
        }

        $committedAt = trim($result->output());

        if ($committedAt === '') {
            return null;
        }

        try {
            return $this->format(Carbon::parse($committedAt));
        } catch (\Throwable) {
            return null;
        }
    }

    protected function buildRefPath(): string
    {
        $path = trim((string) config('app.build_ref_path', 'bootstrap/build-ref'));

        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    protected function normalize(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || strlen($value) > self::MAX_LENGTH || preg_match(self::PATTERN, $value) !== 1) {
            return null;
        }

        return $value;
    }
}
