<?php

use App\Services\EuroRateService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('parses the euro rate from BCV HTML', function () {
    Carbon::setTestNow('2024-01-01 00:00:00');

    $html = <<<'HTML'
    <div id="euro" class="col-sm-12 col-xs-12 ">
        <div class="field-content">
            <div class="row recuadrotsmc">
                <div class="col-sm-6 col-xs-6">
                    <img src="/sites/default/files/euro-04_2.png" class="icono_bss_blanco1">
                    <span> EUR </span>
                </div>
                <div class="col-sm-6 col-xs-6 centrado"><strong> 208,28473732 </strong></div>
            </div>
        </div>
    </div>
    HTML;

    config(['services.bcv.url' => 'https://bcv.test/']);

    Http::fake([
        'https://bcv.test/*' => Http::response($html, 200),
    ]);

    try {
        $service = new EuroRateService();

        $rate = $service->fetchRate();

        expect($rate)->not->toBeNull();
        expect($rate)->toMatchArray([
            'source' => 'BCV',
            'value' => 208.28473732,
            'currency' => 'VES',
        ]);

        expect($rate['effective_at'])->toBeInstanceOf(CarbonInterface::class);
        expect($rate['effective_at']->equalTo(Carbon::now()->utc()))->toBeTrue();
    } finally {
        Carbon::setTestNow();
    }
});
