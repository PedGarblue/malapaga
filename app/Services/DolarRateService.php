<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DolarRateService
{
    private const SOURCE_MAP = [
        'oficial' => 'BCV',
        'paralelo' => 'Paralelo',
    ];

    public function fetchRates(): Collection
    {
        $baseUrl = rtrim(config('services.dolar_api.base_url', 'https://ve.dolarapi.com'), '/');

        $response = Http::baseUrl($baseUrl)
            ->timeout(config('services.dolar_api.timeout', 10))
            ->acceptJson()
            ->get('/v1/dolares')
            ->throw();

        $data = $response->json();

        return collect(is_array($data) ? $data : [])
            ->map(function ($rate) {
                if (! is_array($rate)) {
                    return null;
                }

                return $this->transformRate($rate);
            })
            ->filter()
            ->values();
    }

    private function transformRate(array $rate): ?array
    {
        $sourceKey = Str::of($rate['fuente'] ?? '')->lower()->value();
        $source = self::SOURCE_MAP[$sourceKey] ?? null;

        if ($source === null) {
            return null;
        }

        $value = $rate['promedio'] ?? null;

        if ($value === null) {
            return null;
        }

        $effectiveAt = $this->resolveEffectiveAt($rate['fechaActualizacion'] ?? null);

        return [
            'source' => $source,
            'value' => (float) $value,
            'currency' => 'VES',
            'effective_at' => $effectiveAt,
        ];
    }

    private function resolveEffectiveAt(?string $timestamp): CarbonInterface
    {
        if ($timestamp === null) {
            return now()->utc();
        }

        return Carbon::parse($timestamp)->utc();
    }
}
