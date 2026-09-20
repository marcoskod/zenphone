<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * The single place an order's price goes back to the customer's balance. Every refund
 * path (explicit cancel, the supplier reporting a timeout during a status poll, the scheduled
 * expired-order sweeper) funnels through here, so refunded_at - checked under a row lock
 * - guarantees a customer can never be refunded twice for one order no matter which of
 * those paths races another.
 */
class OrderRefunder
{
    /**
     * @return bool true if this call performed the refund, false if the order was already
     *              refunded or had received an SMS (never refundable).
     */
    public function refund(Order $order, string $status = 'cancelled'): bool
    {
        return DB::transaction(function () use ($order, $status) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked || $locked->refunded_at !== null || $locked->sms_code !== null) {
                return false;
            }

            $locked->update(['status' => $status, 'refunded_at' => now()]);
            $locked->user()->increment('balance', $locked->price_fcfa);

            $order->refresh();

            return true;
        });
    }
}
