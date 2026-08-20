<?php

return [

    'api_key' => env('FIVESIM_API_KEY'),

    'base_url' => env('FIVESIM_BASE_URL', 'https://5sim.net/v1'),

    'margin_percent' => env('FIVESIM_MARGIN_PERCENT', 20),

    'exchange_rate_usd_fcfa' => env('EXCHANGE_RATE_USD_FCFA', 600),

];
