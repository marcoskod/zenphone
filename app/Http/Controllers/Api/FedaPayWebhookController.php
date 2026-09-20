<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FedaPayException;
use App\Http\Controllers\Controller;
use App\Models\PendingPurchase;
use App\Services\FedaPay\FedaPayService;
use App\Services\PurchaseFinalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Safety net for the browser callback: if a customer pays and then loses connection,
 * closes the tab or their phone dies before /api/purchase/pay-confirm runs, FedaPay
 * still notifies us here and the number gets delivered anyway (it then shows up in their
 * history / resumes on their next visit) instead of leaving a paid order undelivered.
 *
 * Nothing in the webhook payload is trusted: it is only used to learn a transaction id.
 * The transaction is then re-fetched from FedaPay with our secret key - the same
 * server-to-server check the browser path uses - and the pending purchase is located
 * from the *verified* transaction's custom_metadata. A forged webhook can therefore do
 * nothing beyond making us re-check a real transaction, and finalize() is idempotent.
 */
class FedaPayWebhookController extends Controller
{
    public function __construct(
        protected FedaPayService $fedaPay,
        protected PurchaseFinalizer $finalizer,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $event = (string) $request->input('name', '');
        $transactionId = $request->input('entity.id');

        // Acknowledge (200) anything that isn't an approved-transaction event so FedaPay
        // doesn't keep retrying events we have no use for.
        if (! str_starts_with($event, 'transaction.approved') || ! $transactionId) {
            return response()->json(['received' => true]);
        }

        try {
            $transaction = $this->fedaPay->verifyTransaction((string) $transactionId);
        } catch (FedaPayException $e) {
            Log::error('FedaPay webhook: verification failed, asking FedaPay to retry', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            // 5xx makes FedaPay retry later, which is what we want for a transient failure.
            return response()->json(['received' => false], 503);
        }

        $pendingId = $transaction['custom_metadata']['pending_purchase_id'] ?? null;
        $pending = $pendingId ? PendingPurchase::find($pendingId) : null;
        $pending ??= PendingPurchase::where('fedapay_transaction_id', (string) $transactionId)->first();

        if (! $pending) {
            // Either not one of our direct-purchase transactions, or the metadata never
            // made it through. The latter would strand a customer's payment, so leave a
            // trace an operator can act on instead of dropping it silently.
            Log::warning('FedaPay webhook: approved transaction matches no pending purchase', [
                'transaction_id' => $transactionId,
                'amount' => $transaction['amount'] ?? null,
                'custom_metadata' => $transaction['custom_metadata'] ?? null,
            ]);

            return response()->json(['received' => true]);
        }

        $result = $this->finalizer->finalize($pending, (string) $transactionId, $transaction);

        if (! $result['ok']) {
            Log::warning('FedaPay webhook: purchase not finalized', [
                'pending_purchase_id' => $pending->id,
                'transaction_id' => $transactionId,
                'reason' => $result['message'],
            ]);
        }

        return response()->json(['received' => true]);
    }
}
