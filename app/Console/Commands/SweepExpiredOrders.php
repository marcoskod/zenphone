<?php

namespace App\Console\Commands;

use App\Exceptions\SmsProviderException;
use App\Models\Order;
use App\Notifications\SmsReceived;
use App\Services\OrderRefunder;
use App\Services\SmsProvider\Contracts\SmsProviderInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SweepExpiredOrders extends Command
{
    protected $signature = 'orders:sweep-expired';

    protected $description = 'Refund customers for numbers that expired without receiving an SMS (covers customers who left the page)';

    public function handle(SmsProviderInterface $sms, OrderRefunder $refunder): int
    {
        $orders = Order::whereNull('sms_code')
            ->whereNull('refunded_at')
            ->where('status', 'pending')
            ->where('expires_at', '<', now()->subMinute())
            ->orderBy('expires_at')
            ->limit(100)
            ->get();

        $refunded = 0;

        foreach ($orders as $order) {
            try {
                $result = $sms->checkOrder($order->provider_order_id);

                $messages = $result['sms'] ?? [];
                $code = is_array($messages) && $messages ? (end($messages)['code'] ?? null) : null;

                if ($code) {
                    // The SMS did arrive while the customer was away - deliver, don't refund.
                    $order->update(['status' => strtolower($result['status'] ?? 'received'), 'sms_code' => $code]);
                    $order->user->notify(new SmsReceived($order));

                    continue;
                }

                $status = strtolower($result['status'] ?? '');

                if (! in_array($status, ['canceled', 'timeout', 'banned'], true)) {
                    // Still nominally alive on the supplier's side past our expiry: cancel it there
                    // first so we never refund a number that could still be billed.
                    $status = strtolower($sms->cancelOrder($order->provider_order_id)['status'] ?? '');
                }

                if (in_array($status, ['canceled', 'timeout', 'banned'], true) && $refunder->refund($order, 'timeout')) {
                    $refunded++;
                }
            } catch (SmsProviderException $e) {
                Log::warning('Expired-order sweep: supplier call failed, will retry next run', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Checked {$orders->count()} expired order(s), refunded {$refunded}.");

        return self::SUCCESS;
    }
}
