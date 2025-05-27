<?php

return [
    'merchant_name' => env('FASPAY_MERCHANT_NAME', null),
    'merchant_id' => env('FASPAY_MERCHANT_ID', null),
    'user_id' => env('FASPAY_USER_ID', null),
    'password' => env('FASPAY_PASSWORD', null),
    'password_prod' => env('FASPAY_PASSWORD_PROD', null),
    'signature' => env('FASPAY_SIGNATURE', null),
    'is_production' => env('FASPAY_IS_PRODUCTION', false),
];
