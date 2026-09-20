<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reservation for a direct-payment purchase (no wallet top-up involved): created with
 * the price locked in at the moment the customer clicks "Acheter", before FedaPay's
 * checkout widget even opens. PurchaseController::payConfirm() turns it into a real Order
 * once FedaPay confirms the exact payment server-to-server - never before.
 */
class PendingPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service',
        'country',
        'price_fcfa',
        'fedapay_transaction_id',
        'status',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'price_fcfa' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
