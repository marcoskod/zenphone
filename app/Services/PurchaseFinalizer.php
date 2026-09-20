<?php

namespace App\Services;

use App\Exceptions\SmsProviderException;
use App\Models\Order;
use App\Models\PendingPurchase;
use App\Services\SmsProvider\Contracts\SmsProviderInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns a paid PendingPurchase into a real Order. Shared by the browser callback
 * (PurchaseController::payConfirm) and the FedaPay webhook, which can both fire for the
 * same payment at nearly the same moment: the PendingPurchase row is locked for the whole
 * operation, so whichever arrives second sees status 'paid' and just returns the existing
 * order - the activation is bought exactly once.
 *
 * The caller is responsible for having verified the FedaPay transaction server-to-server
 * (status approved) and passes the verified transaction in; this class re-checks the
 * amount against the price locked at pay-init time.
 */
class PurchaseFinalizer
{
    public function __construct(protected SmsProviderInterface $sms)
    {
    }

    /**
     * @param  array<string, mixed>  $transaction  the verified FedaPay transaction
     * @return array{ok: true, order: Order}|array{ok: false, message: string, status: int}
     */
    public function finalize(PendingPurchase $pending, string $transactionId, array $transaction): array
    {
        return DB::transaction(function () use ($pending, $transactionId, $transaction) {
            $locked = PendingPurchase::whereKey($pending->id)->lockForUpdate()->first();

            if ($locked->status === 'paid' && $locked->order_id) {
                return ['ok' => true, 'order' => $locked->order];
            }

            if ($locked->status !== 'awaiting_payment') {
                return ['ok' => false, 'message' => 'Cette commande a déjà été traitée.', 'status' => 422];
            }

            if (($transaction['status'] ?? null) !== 'approved') {
                return [
                    'ok' => false,
                    'message' => 'FedaPay n\'a pas confirmé ce paiement (statut : '.($transaction['status'] ?? 'inconnu').').',
                    'status' => 422,
                ];
            }

            if ((float) ($transaction['amount'] ?? 0) < (float) $locked->price_fcfa) {
                return ['ok' => false, 'message' => 'Le montant payé ne correspond pas au prix de la commande.', 'status' => 422];
            }

            // FedaPay transaction ids are sequential, so without this a customer could
            // present someone else's approved transaction as payment for their own order.
            // The widget stamps our pending id into custom_metadata when it creates the
            // transaction; if it is present it must match. (Absent metadata is tolerated
            // rather than rejected - refusing an already-captured payment would strand the
            // customer's money - but check in the sandbox that FedaPay echoes it back.)
            $metadataId = $transaction['custom_metadata']['pending_purchase_id'] ?? null;

            if ($metadataId !== null && (int) $metadataId !== (int) $locked->id) {
                Log::warning('Transaction metadata points at a different purchase', [
                    'pending_purchase_id' => $locked->id,
                    'transaction_id' => $transactionId,
                    'metadata_pending_purchase_id' => $metadataId,
                ]);

                return ['ok' => false, 'message' => 'Cette transaction correspond à une autre commande.', 'status' => 422];
            }

            $usedElsewhere = PendingPurchase::where('fedapay_transaction_id', $transactionId)
                ->where('id', '!=', $locked->id)
                ->exists();

            if ($usedElsewhere) {
                return ['ok' => false, 'message' => 'Cette transaction a déjà été utilisée.', 'status' => 422];
            }

            $locked->update(['fedapay_transaction_id' => $transactionId]);

            try {
                $activation = $this->sms->buyActivation($locked->country, $locked->service);
            } catch (SmsProviderException $e) {
                // Real money was captured but no number could be reserved: hand it back as
                // store credit instead of leaving the customer out of pocket.
                $locked->update(['status' => 'failed']);
                $locked->user()->increment('balance', $locked->price_fcfa);

                Log::warning('Paid purchase could not be fulfilled, credited to balance', [
                    'pending_purchase_id' => $locked->id,
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'ok' => false,
                    'message' => "Le numéro n'a pas pu être réservé après votre paiement. Le montant a été crédité sur votre solde Zen_Sms : ".$e->getMessage(),
                    'status' => 502,
                ];
            }

            $this->warnOnPriceDrift($locked, $activation);

            try {
                $order = Order::create([
                    'user_id' => $locked->user_id,
                    'provider_order_id' => $activation['id'],
                    'service' => $locked->service,
                    'country' => $locked->country,
                    'phone' => $activation['phone'] ?? '',
                    'price_fcfa' => $locked->price_fcfa,
                    'status' => strtolower($activation['status'] ?? 'pending'),
                    'sms_code' => null,
                    'expires_at' => $this->parseExpiresAt($activation),
                ]);

                $locked->update(['status' => 'paid', 'order_id' => $order->id]);
            } catch (\Throwable $e) {
                // Our own write failed after the supplier already reserved (and billed) a number.
                // The transaction rolls back and a retry would buy a second one, so release
                // this one first and leave a loud trace.
                Log::critical('Order write failed after the supplier purchase; cancelling the activation', [
                    'pending_purchase_id' => $locked->id,
                    'provider_order_id' => $activation['id'] ?? null,
                    'error' => $e->getMessage(),
                ]);

                try {
                    $this->sms->cancelOrder((string) $activation['id']);
                } catch (\Throwable) {
                    // already logged above; nothing more we can do from here
                }

                throw $e;
            }

            return ['ok' => true, 'order' => $order];
        });
    }

    /**
     * The quoted catalog price and the price actually charged to the supplier
     * account (buy response) should agree. If the supplier ever changes units/currency or the
     * catalog goes stale, this is the early warning in the logs before margins erode.
     */
    private function warnOnPriceDrift(PendingPurchase $pending, array $activation): void
    {
        if (! isset($activation['price_usd'])) {
            return;
        }

        $expectedFcfa = (float) $pending->price_fcfa;
        $chargedFcfa = (float) $activation['price_usd'] * (float) config('smspool.exchange_rate_usd_fcfa');

        if ($chargedFcfa > $expectedFcfa) {
            Log::warning('The supplier charged more than the customer paid', [
                'pending_purchase_id' => $pending->id,
                'customer_paid_fcfa' => $expectedFcfa,
                'supplier_charged_fcfa_estimate' => $chargedFcfa,
            ]);
        }
    }

    private function parseExpiresAt(array $activation): Carbon
    {
        if (isset($activation['expires'])) {
            try {
                return Carbon::parse($activation['expires']);
            } catch (\Throwable) {
                // fall through to the default below
            }
        }

        return now()->addMinutes(15);
    }
}
