<?php

namespace App\Services;

class RegionDistanceEstimator
{
    /**
     * @var array<string, array{lat: float, lng: float}>
     */
    private array $coordinates = [
        'amsterdam' => ['lat' => 52.3676, 'lng' => 4.9041],
        'rotterdam' => ['lat' => 51.9244, 'lng' => 4.4777],
        'den haag' => ['lat' => 52.0705, 'lng' => 4.3007],
        'utrecht' => ['lat' => 52.0907, 'lng' => 5.1214],
        'eindhoven' => ['lat' => 51.4416, 'lng' => 5.4697],
        'tilburg' => ['lat' => 51.5555, 'lng' => 5.0913],
        'groningen' => ['lat' => 53.2194, 'lng' => 6.5665],
        'almere' => ['lat' => 52.3508, 'lng' => 5.2647],
        'breda' => ['lat' => 51.5719, 'lng' => 4.7683],
        'nijmegen' => ['lat' => 51.8126, 'lng' => 5.8372],
        'apeldoorn' => ['lat' => 52.2112, 'lng' => 5.9699],
        'haarlem' => ['lat' => 52.3874, 'lng' => 4.6462],
        'arnhem' => ['lat' => 51.9851, 'lng' => 5.8987],
        'enschede' => ['lat' => 52.2215, 'lng' => 6.8937],
        'amersfoort' => ['lat' => 52.1561, 'lng' => 5.3878],
        'den bosch' => ['lat' => 51.6978, 'lng' => 5.3037],
        's-hertogenbosch' => ['lat' => 51.6978, 'lng' => 5.3037],
        'zwolle' => ['lat' => 52.5168, 'lng' => 6.0830],
        'leiden' => ['lat' => 52.1601, 'lng' => 4.4970],
        'dordrecht' => ['lat' => 51.8133, 'lng' => 4.6901],
        'zaandam' => ['lat' => 52.4420, 'lng' => 4.8292],
    ];

    /**
     * @var array<string, string>
     */
    private array $postcodePrefixes = [
        '10' => 'amsterdam',
        '11' => 'zaandam',
        '20' => 'haarlem',
        '21' => 'haarlem',
        '23' => 'leiden',
        '25' => 'den haag',
        '26' => 'den haag',
        '30' => 'rotterdam',
        '31' => 'rotterdam',
        '33' => 'dordrecht',
        '35' => 'utrecht',
        '38' => 'amersfoort',
        '50' => 'tilburg',
        '52' => 'den bosch',
        '56' => 'eindhoven',
        '65' => 'nijmegen',
        '68' => 'arnhem',
        '73' => 'apeldoorn',
        '75' => 'enschede',
        '80' => 'zwolle',
        '97' => 'groningen',
    ];

    public function distanceKm(?string $fromCity, ?string $toCity, ?string $fromRegion = null, ?string $toRegion = null): ?float
    {
        $from = $this->coordinates[$this->normalizeLocation($fromCity)] ?? null;
        $to = $this->coordinates[$this->normalizeLocation($toCity)] ?? null;

        if ($from && $to) {
            return round($this->haversine($from['lat'], $from['lng'], $to['lat'], $to['lng']) * 1.2, 1);
        }

        if ($fromRegion && $toRegion && strcasecmp($fromRegion, $toRegion) === 0) {
            return 30.0;
        }

        if ($fromCity && $toCity && strcasecmp($fromCity, $toCity) === 0) {
            return 10.0;
        }

        return null;
    }

    public function travelMinutes(?float $distanceKm): ?int
    {
        return $distanceKm === null ? null : (int) ceil(15 + ($distanceKm * 1.25));
    }

    private function normalize(?string $value): string
    {
        return trim(mb_strtolower((string) $value));
    }

    private function normalizeLocation(?string $value): string
    {
        $normalized = $this->normalize($value);
        $prefix = substr(preg_replace('/\D/', '', $normalized), 0, 2);

        return $this->postcodePrefixes[$prefix] ?? $normalized;
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
