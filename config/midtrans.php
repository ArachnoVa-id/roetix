<?php

return [
    'server_key' => env(
        env('MIDTRANS_IS_PRODUCTION', false) ? 'MIDTRANS_SERVER_KEY' : 'MIDTRANS_SERVER_KEY_SB',
        null
    ),
    'client_key' => env(
        env('MIDTRANS_IS_PRODUCTION', false) ? 'MIDTRANS_CLIENT_KEY' : 'MIDTRANS_CLIENT_KEY_SB',
        null
    ),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => true,
    'is_3ds' => true,
];
