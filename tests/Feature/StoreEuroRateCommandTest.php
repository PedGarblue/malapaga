<?php

use App\Models\Rate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use function Pest\Laravel\artisan;

it('stores the BCV euro rate when available', function () {
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

    artisan('rates:store-euro')->assertExitCode(0);

    $rate = Rate::query()->first();

    expect(Rate::query()->count())->toBe(1);

    expect($rate)->not->toBeNull();
    expect($rate->source)->toBe('BCV');
    expect((float) $rate->value)->toBe(208.2847);
    expect($rate->currency)->toBe('VES');
    expect($rate->effective_at->eq(Carbon::parse('2024-01-01 12:34:56', 'UTC')))->toBeTrue();

    Carbon::setTestNow();
});
