<?php

namespace App\Services\Yandex;

use App\Models\YandexAccount;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class YandexTokenService
{
    private string $tokenEndpoint = 'https://oauth.yandex.com/token';
    private string $clientId;
    private string $clientSecret;
    private Client $http;

    public function __construct()
    {
        $this->clientId = Config::get('integrations.yandex.client_id');
        $this->clientSecret = Config::get('integrations.yandex.client_secret');
        $this->http = new Client(['timeout' => 20]);
    }

    /**
     * Возвращает действительный access_token для аккаунта, обновляя при необходимости
     */
    public function getAccessTokenFor(YandexAccount $account): ?string
    {
        if ($account->revoked) {
            return null;
        }

        $now = Carbon::now();
        if ($account->expires_at && $now->lt($account->expires_at->subMinutes(1))) {
            return $account->access_token; // accessor decrypts
        }

        // Нужно обновить token
        if (!$account->refresh_token) {
            Log::warning('No refresh token for YandexAccount id=' . $account->id);
            return null;
        }

        try {
            $response = $this->http->post($this->tokenEndpoint, [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $account->refresh_token,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            if (isset($body['access_token'])) {
                $account->access_token = $body['access_token'];
                if (isset($body['refresh_token'])) {
                    $account->refresh_token = $body['refresh_token'];
                }
                if (isset($body['expires_in'])) {
                    $account->expires_at = Carbon::now()->addSeconds((int) $body['expires_in']);
                }
                $account->save();
                return $account->access_token;
            }

            Log::error('Invalid refresh response from Yandex', ['body' => $body]);
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to refresh Yandex token: ' . $e->getMessage(), ['account_id' => $account->id]);
            return null;
        }
    }

    /**
     * Exchange authorization code for tokens and create/update account
     */
    public function exchangeCode(string $code, string $redirectUri, ?int $userId = null, ?string $providerUserId = null): ?YandexAccount
    {
        try {
            $response = $this->http->post($this->tokenEndpoint, [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri' => $redirectUri,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);
            if (!isset($body['access_token'])) {
                Log::error('Yandex exchangeCode returned no access_token', ['body' => $body]);
                return null;
            }

            $account = new YandexAccount();
            if ($userId) $account->user_id = $userId;
            if ($providerUserId) $account->provider_user_id = $providerUserId;
            $account->access_token = $body['access_token'];
            $account->refresh_token = $body['refresh_token'] ?? null;
            $account->scopes = $body['scope'] ?? null;
            if (isset($body['expires_in'])) {
                $account->expires_at = Carbon::now()->addSeconds((int) $body['expires_in']);
            }
            $account->save();

            return $account;
        } catch (\Exception $e) {
            Log::error('Failed to exchange code for Yandex token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate token by calling management/v1/counters
     */
    public function validateToken(string $accessToken): bool
    {
        try {
            $client = new Client(['timeout' => 10, 'headers' => [
                'Authorization' => 'OAuth ' . $accessToken,
                'Accept' => 'application/json',
            ]]);

            $resp = $client->get('https://api-metrica.yandex.net/management/v1/counters');
            return $resp->getStatusCode() === 200;
        } catch (\Exception $e) {
            Log::warning('Yandex token validation failed: ' . $e->getMessage());
            return false;
        }
    }
}
