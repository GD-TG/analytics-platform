<?php

namespace App\Services\Yandex;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class YandexOAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl = 'https://oauth.yandex.ru';

    public function __construct()
    {
        $this->clientId = Config::get('integrations.yandex.client_id');
        $this->clientSecret = Config::get('integrations.yandex.client_secret');
    }

    /**
     * Получить OAuth токен используя Client ID и Client Secret
     * Для получения токена нужен authorization code, который получается через браузер
     */
    public function getTokenByCode(string $code, string $redirectUri = 'https://oauth.yandex.ru/verification_code'): ?string
    {
        try {
            $client = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => 30,
            ]);

            $response = $client->post('/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['access_token'])) {
                return $data['access_token'];
            }

            return null;
        } catch (RequestException $e) {
            Log::error('Failed to get OAuth token', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            return null;
        }
    }

    /**
     * Получить URL для авторизации
     */
    public function getAuthorizationUrl(string $redirectUri = 'https://oauth.yandex.ru/verification_code'): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
        ]);

        return "https://oauth.yandex.ru/authorize?{$params}";
    }

    /**
     * Проверить валидность токена
     */
    public function validateToken(string $token): bool
    {
        try {
            $client = new Client([
                'base_uri' => 'https://login.yandex.ru',
                'timeout' => 10,
            ]);

            $response = $client->get('/info', [
                'headers' => [
                    'Authorization' => "OAuth {$token}",
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (RequestException $e) {
            return false;
        }
    }

    /**
     * Получить информацию о токене
     */
    public function getTokenInfo(string $token): ?array
    {
        try {
            $client = new Client([
                'base_uri' => 'https://login.yandex.ru',
                'timeout' => 10,
            ]);

            $response = $client->get('/info', [
                'headers' => [
                    'Authorization' => "OAuth {$token}",
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            Log::error('Failed to get token info', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Получить информацию о пользователе по OAuth токену
     * Используется для авторизации через Yandex ID
     */
    public function getUserInfo(string $token): ?array
    {
        $info = $this->getTokenInfo($token);
        
        if (!$info) {
            return null;
        }

        $firstName = $info['first_name'] ?? '';
        $lastName = $info['last_name'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        if (empty($fullName)) {
            $fullName = $info['real_name'] ?? 'Yandex User';
        }

        $avatar = null;
        if (isset($info['default_avatar_id']) && $info['default_avatar_id']) {
            $avatar = "https://avatars.yandex.net/get-yapic/{$info['default_avatar_id']}/islands-200";
        }

        return [
            'yandex_id' => $info['id'] ?? null,
            'email' => $info['default_email'] ?? ($info['emails'][0] ?? null),
            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'avatar' => $avatar,
        ];
    }

    /**
     * Получить URL для авторизации пользователя (OAuth flow)
     */
    public function getUserAuthorizationUrl(string $redirectUri, array $scopes = ['login:email', 'login:info']): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
        ]);

        return "https://oauth.yandex.ru/authorize?{$params}";
    }

    /**
     * Обменять authorization code на access token для пользователя
     */
    public function exchangeUserCode(string $code, string $redirectUri): ?array
    {
        try {
            $client = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => 30,
            ]);

            $response = $client->post('/token', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['access_token'])) {
                return [
                    'access_token' => $data['access_token'],
                    'expires_in' => $data['expires_in'] ?? null,
                    'refresh_token' => $data['refresh_token'] ?? null,
                ];
            }

            return null;
        } catch (RequestException $e) {
            Log::error('Failed to exchange user code', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
            return null;
        }
    }
}

