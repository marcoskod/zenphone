<?php

return [

    'public_key' => env('FEDAPAY_PUBLIC_KEY'),

    'secret_key' => env('FEDAPAY_SECRET_KEY'),

    'environment' => env('FEDAPAY_ENVIRONMENT', 'sandbox'),

    'base_url' => env('FEDAPAY_ENVIRONMENT', 'sandbox') === 'live'
        ? 'https://api.fedapay.com/v1'
        : 'https://sandbox-api.fedapay.com/v1',

];
