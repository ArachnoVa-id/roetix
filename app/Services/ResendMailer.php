<?php

namespace App\Services;

use GuzzleHttp\Client;

class ResendMailer
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.resend.com/',
            'headers' => [
                'Authorization' => 'Bearer ' . config('mail.mailers.resend.api_key'),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->apiKey = config('mail.mailers.resend.api_key');
    }

    public function send(string $to, string $subject, string $html)
    {
        $from = config('mail.from.address');

        $response = $this->client->post('emails', [
            'json' => [
                'from' => $from,
                'to' => [$to],
                'subject' => $subject,
                'html' => $html,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}
