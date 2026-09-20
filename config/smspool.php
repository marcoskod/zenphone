<?php

return [

    'api_key' => env('SMSPOOL_API_KEY'),

    'base_url' => env('SMSPOOL_BASE_URL', 'https://api.smspool.net'),

    // 200 = the FCFA price is tripled (cost x rate x 3). Overridable live in the admin panel.
    'margin_percent' => env('SMSPOOL_MARGIN_PERCENT', 200),

    'exchange_rate_usd_fcfa' => env('EXCHANGE_RATE_USD_FCFA', 600),

    // Lowest price ever charged/quoted (FCFA): tiny supplier prices can't be collected via Mobile Money.
    'min_price_fcfa' => env('SMSPOOL_MIN_PRICE_FCFA', 100),

    // Safety ceiling passed to SMSPool as max_price: the customer was quoted the cheapest
    // current price, so never let a purchase cost more than that plus this fraction.
    'max_price_tolerance' => env('SMSPOOL_MAX_PRICE_TOLERANCE', 0.15),

];
