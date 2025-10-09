<?php

use App\Console\Commands\StoreDolarRates;
use App\Console\Commands\StoreEuroRate;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

foreach (['12:00', '13:30', '16:00', '21:00'] as $time) {
    Schedule::command(StoreDolarRates::class)
        ->timezone('America/Caracas')
        ->dailyAt($time)
        ->description('Fetch and store VES to USD prices from DolarAPI.');

    Schedule::command(StoreEuroRate::class)
        ->timezone('America/Caracas')
        ->dailyAt($time)
        ->description('Fetch and store VES to EUR price from BCV.');
}
