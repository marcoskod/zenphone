<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fivesim_order_id',
        'service',
        'country',
        'phone',
        'price_fcfa',
        'status',
        'sms_code',
    ];

    protected function casts(): array
    {
        return [
            'fivesim_order_id' => 'integer',
            'price_fcfa' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
