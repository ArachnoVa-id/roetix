<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Log;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // You can exclude specific routes if needed, but it's better 
        // to properly handle CSRF tokens
        // 'payment/webhook', // Example: exclude payment webhooks
        'payment/charge',
    ];
    
    /**
     * Determine if the request has a valid CSRF token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        // For debugging only - remove in production
        Log::debug('CSRF check', [
            'token' => $request->input('_token'),
            'header' => $request->header('X-CSRF-TOKEN'),
            'xsrf' => $request->header('X-XSRF-TOKEN'),
        ]);
        
        return parent::tokensMatch($request);
    }
}