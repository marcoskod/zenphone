<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceAdjustment extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'amount_fcfa',
        'balance_after',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_fcfa' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
