<?php

namespace App\Console\Commands;

use App\Models\Rate;
use App\Services\DolarRateService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

class StoreDolarRates extends Command
{
    protected $signature = 'rates:store-dolar';

    protected $description = 'Fetch and store VES to USD prices from DolarAPI.';

    public function __construct(private readonly DolarRateService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Fetching VES to USD prices from DolarAPI...');

        try {
            $rates = $this->service->fetchRates();
        } catch (Throwable $exception) {
            $this->error('Failed to fetch rates: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($rates->isEmpty()) {
            $this->warn('DolarAPI returned no rates.');

            return self::FAILURE;
        }

        $stored = $this->storeRates($rates);

        $this->info("Stored {$stored} rate records.");

        return self::SUCCESS;
    }

    private function storeRates(Collection $rates): int
    {
        return $rates->reduce(function (int $count, array $rate): int {
            Rate::create([
                'source' => $rate['source'],
                'value' => number_format($rate['value'], 4, '.', ''),
                'currency_from' => 'VES',
                'currency_to' => 'USD',
                'effective_at' => $rate['effective_at'],
            ]);

            return $count + 1;
        }, 0);
    }
}
