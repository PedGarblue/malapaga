<?php

use App\Services\BcvEuroRateService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

it('fetches the euro rate from BCV', function () {
    Carbon::setTestNow(Carbon::parse('2024-01-01 12:34:56', 'UTC'));

    Http::fake([
        'https://www.bcv.org.ve/*' => Http::response(<<<'HTML'
            <html>
                <body>
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
                </body>
            </html>
        HTML, 200),
    ]);

    $service = new BcvEuroRateService();

    $rate = $service->fetchRate();

    expect($rate)->toEqual([
        'source' => 'BCV',
        'value' => 208.28473732,
        'currency_from' => 'VES',
        'currency_to' => 'EUR',
        'effective_at' => Carbon::parse('2024-01-01 12:34:56', 'UTC'),
    ]);

    Carbon::setTestNow();
});

it('returns null when the euro rate cannot be found', function () {
    Http::fake([
        'https://www.bcv.org.ve/*' => Http::response('<html><body>No euro here</body></html>', 200),
    ]);

    $service = new BcvEuroRateService();

    expect($service->fetchRate())->toBeNull();
});
