<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BcvEuroRateService
{
    public function fetchRate(): ?array
    {
        $baseUrl = rtrim(config('services.bcv.base_url', 'https://www.bcv.org.ve'), '/');

        $response = Http::baseUrl($baseUrl)
            ->timeout(config('services.bcv.timeout', 10))
            ->get('/')
            ->throw();

        $value = $this->extractRateValue($response->body());

        if ($value === null) {
            return null;
        }

        return [
            'source' => 'BCV',
            'value' => $value,
            'currency' => 'VES',
            'effective_at' => now()->utc(),
        ];
    }

    private function extractRateValue(string $html): ?float
    {
        if ($html === '') {
            return null;
        }

        $dom = new DOMDocument();
        $useInternalErrors = libxml_use_internal_errors(true);

        if ($dom->loadHTML($html) === false) {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);

            return null;
        }

        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query("//div[@id='euro']//strong");

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $rawValue = $nodes->item(0)?->textContent ?? '';
        $normalized = Str::of($rawValue)
            ->replace(["\u{00a0}", ' '], '')
            ->trim();

        if ($normalized->isEmpty()) {
            return null;
        }

        $normalized = $normalized
            ->replace('.', '')
            ->replace(',', '.')
            ->value();

        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}
