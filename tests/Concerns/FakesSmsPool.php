<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Http;

/**
 * Canned SMSPool HTTP responses shaped like the real API (verified against
 * https://documenter.getpostman.com/view/30155063/2s9YXmZ1JY). Defaults describe a tiny
 * catalog: countries Russia (id 4), Benin (id 97), United States (id 1); services WhatsApp
 * (1012), Telegram (907); WhatsApp costs $1.00 in every country.
 *
 * Usage - override only what a test cares about, keyed by URL pattern:
 *
 *   Http::fake($this->smsPoolStubs([
 *       '*\/sms\/check' => $this->smsPoolCheck(3, '654321'),
 *   ]));
 *
 * App codes used in tests: countries "russia", "benin", "unitedstates"; services
 * "whatsapp", "telegram". SMSPool order ids are strings, e.g. "ABC12345".
 */
trait FakesSmsPool
{
    protected function smsPoolStubs(array $overrides = []): array
    {
        return $overrides + [
            '*/country/retrieve_all' => Http::response([
                ['ID' => 1, 'name' => 'United States', 'short_name' => 'US', 'cc' => '1', 'region' => 'North America'],
                ['ID' => 4, 'name' => 'Russia', 'short_name' => 'RU', 'cc' => '7', 'region' => 'Europe'],
                ['ID' => 97, 'name' => 'Benin', 'short_name' => 'BJ', 'cc' => '229', 'region' => 'Africa'],
            ], 200),
            '*/service/retrieve_all' => Http::response([
                ['ID' => 907, 'name' => 'Telegram', 'favourite' => 0],
                ['ID' => 1012, 'name' => 'WhatsApp', 'favourite' => 0],
            ], 200),
            '*/request/pricing' => $this->smsPoolPricing(1.0),
            '*/purchase/sms' => $this->smsPoolBuy(),
            '*/sms/check' => $this->smsPoolCheck(1),
            '*/sms/cancel' => Http::response(['success' => 1, 'message' => 'The order has been cancelled, and you have been refunded 1.00 dollars.'], 200),
            '*/request/balance' => Http::response(['balance' => '25.00'], 200),
        ];
    }

    /** WhatsApp (1012) at $priceUsd, plus Telegram (907) at double that, in every country. */
    protected function smsPoolPricing(float $priceUsd): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response([
            ['service' => 1012, 'service_name' => 'WhatsApp', 'country' => 97, 'country_name' => 'Benin', 'short_name' => 'BJ', 'pool' => 3, 'price' => number_format($priceUsd, 2, '.', '')],
            ['service' => 1012, 'service_name' => 'WhatsApp', 'country' => 97, 'country_name' => 'Benin', 'short_name' => 'BJ', 'pool' => 12, 'price' => number_format($priceUsd * 2, 2, '.', '')],
            ['service' => 907, 'service_name' => 'Telegram', 'country' => 97, 'country_name' => 'Benin', 'short_name' => 'BJ', 'pool' => 3, 'price' => number_format($priceUsd * 2, 2, '.', '')],
        ], 200);
    }

    /** A successful purchase/sms response. */
    protected function smsPoolBuy(string $orderId = 'ABC12345', string $number = '22961234567', string $cost = '1.00'): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response([
            'success' => 1,
            'number' => (int) $number,
            'cc' => '229',
            'phonenumber' => substr($number, 3),
            'order_id' => $orderId,
            'country' => 'Benin',
            'service' => 'WhatsApp',
            'pool' => 3,
            'expires_in' => 900,
            'expiration' => now()->addMinutes(15)->timestamp,
            'message' => "You have succesfully ordered a WhatsApp number from pool: Foxtrot for {$cost}.",
            'cost' => $cost,
            'cost_in_cents' => (int) round((float) $cost * 100),
        ], 200);
    }

    /** purchase/sms refused: no stock. */
    protected function smsPoolOutOfStock(): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response([
            'message' => "<p>Pool <b>Foxtrot</b>: <p>We couldn't find an available phone number for you, please try again later!</p></p>",
            'success' => 0,
            'pools' => ['Foxtrot' => ['success' => 0, 'type' => 'OUT_OF_STOCK']],
            'errors' => [['message' => "We couldn't find an available phone number for you, please try again later!"]],
        ], 422);
    }

    /**
     * A sms/check response. Status: 1 pending, 2 expired, 3 completed (pass the code),
     * 5 cancelled, 6 refunded.
     */
    protected function smsPoolCheck(int $status, ?string $code = null): \GuzzleHttp\Promise\PromiseInterface
    {
        $body = ['status' => $status, 'resend' => 0, 'expiration' => now()->addMinutes(10)->timestamp];

        if ($code !== null) {
            $body += ['sms' => $code, 'full_sms' => "Your code is {$code}"];
        }

        return Http::response($body, 200);
    }

    /** sms/cancel refused, e.g. "We could not find this order!" */
    protected function smsPoolCancelRefused(string $message = 'This order can not be cancelled.'): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response(['success' => 0, 'message' => $message], 400);
    }
}
