<?php

namespace Tests\Unit\Services;

use App\Exceptions\SmsProviderException;
use App\Services\SmsProvider\Contracts\SmsProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesSmsPool;
use Tests\TestCase;

class SmsPoolServiceTest extends TestCase
{
    use FakesSmsPool;

    protected function setUp(): void
    {
        parent::setUp();

        config(['smspool.api_key' => 'test-key', 'smspool.base_url' => 'https://api.smspool.net']);
    }

    /** Http::fake() stubs stack and the FIRST match wins, so re-faking within one test needs a clean factory. */
    private function fakeAgain(array $overrides = []): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake($this->smsPoolStubs($overrides));
    }

    private function sms(): SmsProviderInterface
    {
        return app(SmsProviderInterface::class);
    }

    public function test_countries_get_slug_codes_and_iso_and_virtual_ones_are_hidden(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/country/retrieve_all' => Http::response([
                ['ID' => 97, 'name' => 'Benin', 'short_name' => 'BJ', 'cc' => '229', 'region' => 'Africa'],
                ['ID' => 1, 'name' => 'United States', 'short_name' => 'US', 'cc' => '1', 'region' => 'North America'],
                ['ID' => 500, 'name' => 'United States (Virtual)', 'short_name' => 'US', 'cc' => '1', 'region' => 'Virtuals'],
            ], 200),
        ]));

        $this->assertSame([
            ['code' => 'benin', 'name' => 'Benin', 'iso' => 'BJ'],
            ['code' => 'unitedstates', 'name' => 'United States', 'iso' => 'US'],
        ], $this->sms()->getCountries());
    }

    public function test_services_use_the_cheapest_pool_and_slug_the_name_before_the_slash(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/service/retrieve_all' => Http::response([
                ['ID' => 395, 'name' => 'Google/Gmail'],
                ['ID' => 457, 'name' => 'Instagram / Threads'],
                ['ID' => 900, 'name' => 'Instagram'],
                ['ID' => 1012, 'name' => 'WhatsApp'],
            ], 200),
            '*/request/pricing' => Http::response([
                ['service' => 1012, 'pool' => 12, 'price' => '1.33'],
                ['service' => 1012, 'pool' => 3, 'price' => '0.60'],
                ['service' => 395, 'pool' => 3, 'price' => '0.20'],
                ['service' => 900, 'pool' => 3, 'price' => '0.10'],
                ['service' => 99999, 'pool' => 3, 'price' => '0.05'], // unknown service id: skipped
                ['service' => 457, 'pool' => 3, 'price' => '0'],      // free/zero: not orderable
            ], 200),
        ]));

        $services = collect($this->sms()->getServices('benin'))->keyBy('code');

        $this->assertEqualsCanonicalizing(['google', 'instagram-900', 'whatsapp'], $services->keys()->all());
        $this->assertSame(0.60, $services['whatsapp']['price_usd']);
        $this->assertSame('Google/Gmail', $services['google']['label']);
        $this->assertSame(0.10, $services['instagram-900']['price_usd']);
    }

    public function test_get_price_is_the_cheapest_pool_or_null_when_not_offered(): void
    {
        Http::fake($this->smsPoolStubs());

        $this->assertSame(1.0, $this->sms()->getPrice('benin', 'whatsapp'));
        $this->assertNull($this->sms()->getPrice('benin', 'nonexistent'));
    }

    public function test_an_unknown_country_is_rejected_with_a_clear_error(): void
    {
        Http::fake($this->smsPoolStubs());

        $this->expectException(SmsProviderException::class);
        $this->expectExceptionMessage('Pays inconnu.');

        $this->sms()->getPrice('atlantis', 'whatsapp');
    }

    public function test_buy_sends_ids_key_and_a_price_ceiling_and_normalizes_the_response(): void
    {
        Http::fake($this->smsPoolStubs(['*/purchase/sms' => $this->smsPoolBuy('ZXCV9876', '22961234567', '1.00')]));

        $activation = $this->sms()->buyActivation('benin', 'whatsapp');

        $this->assertSame('ZXCV9876', $activation['id']);
        $this->assertSame('+22961234567', $activation['phone']);
        $this->assertSame('PENDING', $activation['status']);
        $this->assertSame(1.0, $activation['price_usd']);
        $this->assertNotNull($activation['expires']);

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), '/purchase/sms')) {
                return false;
            }

            return $request['country'] == 97
                && $request['service'] == 1012
                && $request['key'] === 'test-key'
                && (float) $request['max_price'] === 1.15
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_buy_out_of_stock_gives_a_customer_friendly_message(): void
    {
        Http::fake($this->smsPoolStubs(['*/purchase/sms' => $this->smsPoolOutOfStock()]));

        try {
            $this->sms()->buyActivation('benin', 'whatsapp');
            $this->fail('Expected an exception');
        } catch (SmsProviderException $e) {
            $this->assertStringContainsString('Aucun numéro disponible', $e->getMessage());
            $this->assertStringNotContainsString('<p>', $e->getMessage());
        }
    }

    public function test_buy_for_a_service_not_offered_never_calls_the_purchase_endpoint(): void
    {
        Http::fake($this->smsPoolStubs());

        try {
            $this->sms()->buyActivation('benin', 'nonexistent');
            $this->fail('Expected an exception');
        } catch (SmsProviderException) {
            Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/purchase/sms'));
        }
    }

    public function test_provider_balance_problems_are_hidden_from_customers(): void
    {
        Http::fake($this->smsPoolStubs([
            '*/purchase/sms' => Http::response(['success' => 0, 'message' => 'You do not have enough balance.', 'errors' => [['message' => 'You do not have enough balance.']]], 400),
        ]));

        try {
            $this->sms()->buyActivation('benin', 'whatsapp');
            $this->fail('Expected an exception');
        } catch (SmsProviderException $e) {
            $this->assertStringNotContainsString('balance', strtolower($e->getMessage()));
            $this->assertStringContainsString('momentanément indisponible', $e->getMessage());
        }
    }

    public function test_check_order_maps_statuses(): void
    {
        $cases = [
            [1, null, 'PENDING'],
            [3, '654321', 'RECEIVED'],
            [2, null, 'TIMEOUT'],
            [5, null, 'CANCELED'],
            [6, null, 'CANCELED'],
            [99, null, 'PENDING'], // unknown code: never guess "dead" (that would refund a live order)
        ];

        foreach ($cases as [$code, $sms, $expected]) {
            $this->fakeAgain(['*/sms/check' => $this->smsPoolCheck($code, $sms)]);

            $result = $this->sms()->checkOrder('ABC12345');

            $this->assertSame($expected, $result['status'], "SMSPool status {$code}");
        }
    }

    public function test_check_order_exposes_the_sms_code(): void
    {
        Http::fake($this->smsPoolStubs(['*/sms/check' => $this->smsPoolCheck(3, '112233')]));

        $result = $this->sms()->checkOrder('ABC12345');

        $this->assertSame('112233', $result['sms'][0]['code']);

        $this->fakeAgain(['*/sms/check' => $this->smsPoolCheck(1)]);
        $this->assertSame([], $this->sms()->checkOrder('ABC12345')['sms']);
    }

    public function test_cancel_succeeds_and_a_refusal_surfaces_the_providers_message(): void
    {
        Http::fake($this->smsPoolStubs());
        $this->assertSame('CANCELED', $this->sms()->cancelOrder('ABC12345')['status']);

        $this->fakeAgain(['*/sms/cancel' => $this->smsPoolCancelRefused('We could not find this order!')]);

        $this->expectException(SmsProviderException::class);
        $this->expectExceptionMessage('We could not find this order!');
        $this->sms()->cancelOrder('NOPE');
    }

    public function test_balance_is_read_and_null_on_failure(): void
    {
        Http::fake($this->smsPoolStubs());
        $this->assertSame(25.0, $this->sms()->getBalance());

        $this->fakeAgain(['*/request/balance' => Http::response(['success' => 0, 'message' => 'bad key'], 401)]);
        $this->assertNull($this->sms()->getBalance());
    }

    public function test_a_network_failure_becomes_a_provider_exception(): void
    {
        Http::fake(['*' => fn () => throw new ConnectionException('timeout')]);

        $this->expectException(SmsProviderException::class);
        $this->sms()->getCountries();
    }
}
