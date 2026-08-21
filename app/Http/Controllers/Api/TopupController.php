<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\FedaPayException;
use App\Http\Controllers\Controller;
use App\Models\Topup;
use App\Notifications\TopupConfirmed;
use App\Services\FedaPay\FedaPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TopupController extends Controller
{
    public function __construct(protected FedaPayService $fedaPay)
    {
    }

    /**
     * POST /api/topup/confirm - called after the client-side FedaPay checkout.js widget
     * reports an APPROVED result. The client callback is never trusted alone: this
     * verifies the transaction server-to-server against FedaPay's API using the secret
     * key, and only credits balance / marks the Topup row confirmed once FedaPay itself
     * confirms status "approved". Idempotent via topups.external_reference, so a
     * duplicate confirm call (e.g. a retried request) never double-credits.
     */
    public function confirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'string'],
            'operator' => ['nullable', 'string'],
            'phone_number' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        $existing = Topup::where('external_reference', $validated['transaction_id'])->first();

        if ($existing && $existing->status === 'confirmed') {
            return response()->json([
                'status' => 'confirmed',
                'balance' => (float) $user->fresh()->balance,
            ]);
        }

        try {
            $transaction = $this->fedaPay->verifyTransaction($validated['transaction_id']);
        } catch (FedaPayException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        if (($transaction['status'] ?? null) !== 'approved') {
            return response()->json([
                'message' => 'FedaPay n\'a pas confirmé ce paiement (statut : '.($transaction['status'] ?? 'inconnu').').',
            ], 422);
        }

        $amountFcfa = (float) ($transaction['amount'] ?? 0);

        if ($amountFcfa <= 0) {
            return response()->json(['message' => 'Montant de transaction invalide.'], 422);
        }

        $topup = DB::transaction(function () use ($existing, $user, $validated, $amountFcfa) {
            if ($existing) {
                $existing->update(['status' => 'confirmed', 'amount_fcfa' => $amountFcfa]);
                $topup = $existing;
            } else {
                $topup = Topup::create([
                    'user_id' => $user->id,
                    'amount_fcfa' => $amountFcfa,
                    'operator' => $validated['operator'] ?? null,
                    'phone_number' => $validated['phone_number'] ?? null,
                    'status' => 'confirmed',
                    'external_reference' => $validated['transaction_id'],
                ]);
            }

            $user->increment('balance', $amountFcfa);

            return $topup;
        });

        // Only reached once server-side verification actually succeeded and balance was
        // credited above - never sent speculatively.
        $user->notify(new TopupConfirmed($topup));

        return response()->json([
            'status' => 'confirmed',
            'balance' => (float) $user->fresh()->balance,
        ]);
    }
}
