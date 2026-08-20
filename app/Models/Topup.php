<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Topup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount_fcfa',
        'operator',
        'phone_number',
        'status',
        'external_reference',
    ];

    protected function casts(): array
    {
        return [
            'amount_fcfa' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
