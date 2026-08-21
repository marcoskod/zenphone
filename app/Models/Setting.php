<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const MARGIN_PERCENT_KEY = 'margin_percent';

    /**
     * Cached for a short time since PricingService::calculatePrice() may be called on
     * every /api/price request - avoids a settings-table query per pricing lookup while
     * still picking up an admin change within a minute, without a deploy.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", now()->addMinute(), function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget("setting.{$key}");
    }
}
