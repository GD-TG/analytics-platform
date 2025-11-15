<?php

namespace App\Http\Controllers\Yandex;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Services\Yandex\YandexTokenService;
use App\Models\YandexAccount;

class YandexAuthController extends Controller
{
    private YandexTokenService $tokenService;

    public function __construct(YandexTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    // Возвращает URL авторизации Yandex (Authorization Code)
    public function authUrl(Request $request)
    {
        $redirectUri = $request->get('redirect_uri', url('/auth/yandex/callback'));
        $clientId = Config::get('integrations.yandex.client_id');
        $scope = implode(' ', ['login:info', 'login:email', 'metrika:read', 'direct:read']);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
        ]);

        $url = "https://oauth.yandex.ru/authorize?{$params}";
        return response()->json(['auth_url' => $url]);
    }

    // Обмен кода на токены и сохранение аккаунта
    public function exchangeCode(Request $request)
    {
        $code = $request->post('code');
        $redirectUri = $request->post('redirect_uri', url('/auth/yandex/callback'));
        $userId = auth()->id(); // Требуется авторизация через middleware

        if (!$code) {
            return response()->json(['message' => 'code is required'], 400);
        }

        if (!$userId) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $account = $this->tokenService->exchangeCode($code, $redirectUri, $userId);

        if (!$account) {
            return response()->json(['message' => 'Failed to exchange code'], 500);
        }

        return response()->json(['success' => true, 'account_id' => $account->id]);
    }

    // Validate token (for quick health check)
    public function validateToken(Request $request)
    {
        $token = $request->get('token') ?: Config::get('integrations.yandex.oauth_token');
        if (!$token) {
            return response()->json(['valid' => false, 'message' => 'No token configured'], 400);
        }

        $valid = $this->tokenService->validateToken($token);
        return response()->json(['valid' => $valid]);
    }

    // List counters for current user's account (requires user_id validation)
    public function listCounters(Request $request)
    {
        $accountId = $request->get('account_id');
        $userId = auth()->id();

        if (!$accountId) {
            return response()->json(['message' => 'account_id is required'], 400);
        }

        $account = YandexAccount::where('id', $accountId)->where('user_id', $userId)->first();
        if (!$account) {
            return response()->json(['message' => 'account not found or unauthorized'], 404);
        }

        $accessToken = $this->tokenService->getAccessTokenFor($account);
        if (!$accessToken) {
            return response()->json(['message' => 'no access token'], 403);
        }

        $client = new \GuzzleHttp\Client(['timeout' => 15, 'headers' => [
            'Authorization' => 'OAuth ' . $accessToken,
            'Accept' => 'application/json'
        ]]);

        try {
            $resp = $client->get('https://api-metrica.yandex.net/management/v1/counters');
            $data = json_decode((string) $resp->getBody(), true);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => 'failed to list counters', 'error' => $e->getMessage()], 500);
        }
    }

    // Save selected counters to yandex_counters table (requires user_id validation)
    public function saveCounters(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|integer',
            'counters' => 'required|array',
            'project_id' => 'nullable|integer'
        ]);

        $userId = auth()->id();
        $account = YandexAccount::where('id', $data['account_id'])->where('user_id', $userId)->first();
        if (!$account) {
            return response()->json(['message' => 'account not found or unauthorized'], 404);
        }

        $saved = [];
        foreach ($data['counters'] as $counter) {
            $counterId = $counter['id'] ?? ($counter['counterId'] ?? null);
            $name = $counter['name'] ?? ($counter['title'] ?? '');
            if (!$counterId) continue;

            $yc = \App\Models\YandexCounter::updateOrCreate(
                ['counter_id' => (int) $counterId, 'project_id' => $data['project_id'] ?? null],
                ['name' => $name, 'is_primary' => false]
            );

            $saved[] = ['id' => $yc->id, 'counter_id' => $yc->counter_id];
        }

        return response()->json(['success' => true, 'saved' => $saved]);
    }
}


