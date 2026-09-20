<?php

return [

    'api_key' => env('SMSPOOL_API_KEY'),

    'base_url' => env('SMSPOOL_BASE_URL', 'https://api.smspool.net'),

    'margin_percent' => env('SMSPOOL_MARGIN_PERCENT', 20),

    'exchange_rate_usd_fcfa' => env('EXCHANGE_RATE_USD_FCFA', 600),

    // Safety ceiling passed to SMSPool as max_price: the customer was quoted the cheapest
    // current price, so never let a purchase cost more than that plus this fraction.
    'max_price_tolerance' => env('SMSPOOL_MAX_PRICE_TOLERANCE', 0.15),

];
