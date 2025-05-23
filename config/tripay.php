<?php

return [
    'api_key' => env('TRIPAY_API_KEY', null),
    'private_key' => env('TRIPAY_PRIVATE_KEY', null),
    'merchant_code' => env('TRIPAY_MERCHANT_CODE', null),
    'api_key_sb' => env('TRIPAY_API_KEY_SB', null),
    'private_key_sb' => env('TRIPAY_PRIVATE_KEY_SB', null),
    'merchant_code_sb' => env('TRIPAY_MERCHANT_CODE_SB', null),
    'is_production' => env('TRIPAY_IS_PRODUCTION', false),
];
