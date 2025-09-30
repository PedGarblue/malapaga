<?php

namespace App\Services;

use Carbon\CarbonInterface;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class EuroRateService
{
    private const DEFAULT_URL = 'https://www.bcv.org.ve/';
    private const SOURCE = 'BCV';

    public function fetchRate(): ?array
    {
        $response = Http::timeout((int) config('services.bcv.timeout', 10))
            ->get(config('services.bcv.url', self::DEFAULT_URL))
            ->throw();

        $value = $this->extractEuroValue($response->body());

        if ($value === null) {
            return null;
        }

        return [
            'source' => self::SOURCE,
            'value' => $value,
            'currency' => 'VES',
            'effective_at' => $this->resolveEffectiveAt(),
        ];
    }

    private function extractEuroValue(string $html): ?float
    {
        $html = trim($html);

        if ($html === '') {
            return null;
        }

        $internalErrors = libxml_use_internal_errors(true);

        $document = new DOMDocument();
        $loaded = $document->loadHTML($html);

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        if ($loaded === false) {
            return null;
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query("//div[@id='euro']//strong");

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $rawValue = trim($nodes->item(0)?->textContent ?? '');

        if ($rawValue === '') {
            return null;
        }

        return $this->normalizeNumericValue($rawValue);
    }

    private function normalizeNumericValue(string $value): ?float
    {
        $value = str_replace(["\u{00A0}", ' '], '', $value);

        $digits = preg_replace('/[^0-9,.-]/u', '', $value);

        if ($digits === null || $digits === '') {
            return null;
        }

        $digits = str_replace('.', '', $digits);
        $digits = str_replace(',', '.', $digits);

        if (! is_numeric($digits)) {
            return null;
        }

        return (float) $digits;
    }

    private function resolveEffectiveAt(): CarbonInterface
    {
        return Carbon::now()->utc();
    }
}
