<?php

namespace Tests\Unit\Services;

use App\Exceptions\FiveSimException;
use App\Services\FiveSim\Contracts\FiveSimServiceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FiveSimServiceTest extends TestCase
{
    private function service(): FiveSimServiceInterface
    {
        return app(FiveSimServiceInterface::class);
    }

    public function test_get_products_returns_the_decoded_response(): void
    {
        Http::fake([
            '*/guest/products/russia/any' => Http::response([
                'whatsapp' => ['Category' => 'activation', 'Qty' => 120, 'Price' => 15.5],
            ], 200),
        ]);

        $result = $this->service()->getProducts('russia', 'any');

        $this->assertSame(15.5, $result['whatsapp']['Price']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/guest/products/russia/any'));
    }

    public function test_get_products_are_cached_for_five_minutes(): void
    {
        Http::fake([
            '*/guest/products/*' => Http::response([
                'whatsapp' => ['Category' => 'activation', 'Qty' => 120, 'Price' => 15.5],
            ], 200),
        ]);

        $this->service()->getProducts('russia', 'any');
        $this->service()->getProducts('russia', 'any');

        Http::assertSentCount(1);
    }

    public function test_get_countries_returns_the_decoded_response(): void
    {
        Http::fake([
            '*/guest/countries' => Http::response([
                'russia' => ['iso' => ['ru' => 1], 'prefix' => ['7' => 1], 'text_en' => 'Russia'],
            ], 200),
        ]);

        $result = $this->service()->getCountries();

        $this->assertArrayHasKey('russia', $result);
    }

    public function test_get_countries_are_cached_for_five_minutes(): void
    {
        Http::fake([
            '*/guest/countries' => Http::response(['russia' => ['text_en' => 'Russia']], 200),
        ]);

        $this->service()->getCountries();
        $this->service()->getCountries();

        Http::assertSentCount(1);
    }

    public function test_buy_activation_returns_the_order_data(): void
    {
        Http::fake([
            '*/user/buy/activation/russia/any/whatsapp' => Http::response([
                'id' => 123456,
                'phone' => '+79001234567',
                'operator' => 'any',
                'product' => 'whatsapp',
                'price' => 15.5,
                'status' => 'PENDING',
                'expires' => '2026-08-20T07:10:00Z',
                'sms' => null,
                'country' => 'russia',
            ], 200),
        ]);

        $result = $this->service()->buyActivation('russia', 'any', 'whatsapp');

        $this->assertSame(123456, $result['id']);
        $this->assertSame('+79001234567', $result['phone']);
        $this->assertSame(15.5, $result['price']);
        $this->assertSame('PENDING', $result['status']);
        $this->assertArrayHasKey('expires', $result);
    }

    public function test_buy_activation_throws_five_sim_exception_on_error_response(): void
    {
        Http::fake([
            '*/user/buy/activation/*' => Http::response(['error' => 'not enough user balance'], 400),
        ]);

        try {
            $this->service()->buyActivation('russia', 'any', 'whatsapp');
            $this->fail('Expected FiveSimException was not thrown.');
        } catch (FiveSimException $e) {
            $this->assertSame('not enough user balance', $e->getMessage());
            $this->assertSame(400, $e->statusCode());
        }
    }

    public function test_buy_activation_does_not_retry_a_definitive_error_response(): void
    {
        Http::fake([
            '*/user/buy/activation/*' => Http::response(['error' => 'not enough user balance'], 400),
        ]);

        try {
            $this->service()->buyActivation('russia', 'any', 'whatsapp');
        } catch (FiveSimException) {
            // expected
        }

        // Only one HTTP call: a definitive non-2xx response must never be auto-retried,
        // since buyActivation may have already billed the 5sim account for it.
        Http::assertSentCount(1);
    }

    public function test_buy_activation_retries_and_recovers_from_a_transient_connection_failure(): void
    {
        $attempts = 0;

        Http::fake(function () use (&$attempts) {
            $attempts++;

            if ($attempts < 2) {
                throw new ConnectionException('Connection timed out.');
            }

            return Http::response(['id' => 1, 'phone' => '+7900', 'status' => 'PENDING'], 200);
        });

        $result = $this->service()->buyActivation('russia', 'any', 'whatsapp');

        $this->assertSame(2, $attempts);
        $this->assertSame('PENDING', $result['status']);
    }

    public function test_buy_activation_throws_after_exhausting_retries_on_connection_failure(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out.');
        });

        $this->expectException(FiveSimException::class);

        $this->service()->buyActivation('russia', 'any', 'whatsapp');
    }

    public function test_check_order_returns_status_and_sms_once_received(): void
    {
        Http::fake([
            '*/user/check/123456' => Http::response([
                'id' => 123456,
                'status' => 'RECEIVED',
                'phone' => '+79001234567',
                'price' => 15.5,
                'country' => 'russia',
                'sms' => [
                    ['sender' => 'WhatsApp', 'text' => 'Your code: 123-456', 'code' => '123456'],
                ],
            ], 200),
        ]);

        $result = $this->service()->checkOrder(123456);

        $this->assertSame('RECEIVED', $result['status']);
        $this->assertSame('123456', $result['sms'][0]['code']);
    }

    public function test_check_order_throws_five_sim_exception_when_order_not_found(): void
    {
        Http::fake([
            '*/user/check/*' => Http::response('order not found', 404),
        ]);

        try {
            $this->service()->checkOrder(999999);
            $this->fail('Expected FiveSimException was not thrown.');
        } catch (FiveSimException $e) {
            $this->assertSame('order not found', $e->getMessage());
            $this->assertSame(404, $e->statusCode());
        }
    }

    public function test_cancel_order_returns_canceled_status(): void
    {
        Http::fake([
            '*/user/cancel/123456' => Http::response(['id' => 123456, 'status' => 'CANCELED'], 200),
        ]);

        $result = $this->service()->cancelOrder(123456);

        $this->assertSame('CANCELED', $result['status']);
    }

    public function test_cancel_order_throws_five_sim_exception_on_error(): void
    {
        Http::fake([
            '*/user/cancel/*' => Http::response('order expired', 400),
        ]);

        try {
            $this->service()->cancelOrder(123456);
            $this->fail('Expected FiveSimException was not thrown.');
        } catch (FiveSimException $e) {
            $this->assertSame('order expired', $e->getMessage());
            $this->assertSame(400, $e->statusCode());
        }
    }

    public function test_finish_order_returns_finished_status(): void
    {
        Http::fake([
            '*/user/finish/123456' => Http::response(['id' => 123456, 'status' => 'FINISHED'], 200),
        ]);

        $result = $this->service()->finishOrder(123456);

        $this->assertSame('FINISHED', $result['status']);
    }

    public function test_finish_order_throws_five_sim_exception_on_error(): void
    {
        Http::fake([
            '*/user/finish/*' => Http::response('order not found', 404),
        ]);

        try {
            $this->service()->finishOrder(999999);
            $this->fail('Expected FiveSimException was not thrown.');
        } catch (FiveSimException $e) {
            $this->assertSame('order not found', $e->getMessage());
            $this->assertSame(404, $e->statusCode());
        }
    }
}
