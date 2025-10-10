<?php

namespace App\Console\Commands;

use App\Models\Rate;
use App\Services\BcvEuroRateService;
use Illuminate\Console\Command;
use Throwable;

class StoreEuroRate extends Command
{
    protected $signature = 'rates:store-euro';

    protected $description = 'Fetch and store VES to EUR price from BCV.';

    public function __construct(private readonly BcvEuroRateService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Fetching VES to EUR price from BCV...');

        try {
            $rate = $this->service->fetchRate();
        } catch (Throwable $exception) {
            $this->error('Failed to fetch euro rate: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($rate === null) {
            $this->warn('BCV returned no euro rate.');

            return self::FAILURE;
        }

        Rate::create([
            'source' => $rate['source'],
            'value' => $rate['value'],
            'currency_from' => 'VES',
            'currency_to' => 'EUR',
            'effective_at' => $rate['effective_at'],
        ]);

        $this->info('Stored euro rate record.');

        return self::SUCCESS;
    }
}
