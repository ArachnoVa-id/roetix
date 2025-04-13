<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY', null),
    'client_key' => env('MIDTRANS_CLIENT_KEY', null),
    'server_key_sb' => env('MIDTRANS_SERVER_KEY_SB', null),
    'client_key_sb' => env('MIDTRANS_CLIENT_KEY_SB', null),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => true,
    'is_3ds' => true,
];
