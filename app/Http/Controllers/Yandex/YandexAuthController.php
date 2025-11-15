<?php

namespace App\Http\Controllers\Yandex;

use App\Http\Controllers\Controller;
use App\Services\Yandex\YandexOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class YandexAuthController extends Controller
{
    private YandexOAuthService $oauthService;

    public function __construct(YandexOAuthService $oauthService)
    {
        $this->oauthService = $oauthService;
    }

    /**
     * Получить URL для авторизации
     */
    public function getAuthUrl(Request $request): JsonResponse
    {
        $redirectUri = $request->get('redirect_uri', 'https://oauth.yandex.ru/verification_code');
        $url = $this->oauthService->getAuthorizationUrl($redirectUri);

        return response()->json([
            'success' => true,
            'data' => [
                'auth_url' => $url,
                'instructions' => 'Перейдите по ссылке, авторизуйтесь и скопируйте код из URL',
            ],
        ]);
    }

    /**
     * Обменять код на токен
     */
    public function exchangeCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'redirect_uri' => 'sometimes|string',
        ]);

        $code = $request->get('code');
        $redirectUri = $request->get('redirect_uri', 'https://oauth.yandex.ru/verification_code');

        $token = $this->oauthService->getTokenByCode($code, $redirectUri);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить токен. Проверьте код авторизации.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Токен успешно получен',
            'data' => [
                'token' => $token,
                'instructions' => 'Добавьте этот токен в .env как YANDEX_OAUTH_TOKEN',
            ],
        ]);
    }

    /**
     * Проверить валидность текущего токена
     */
    public function validateToken(Request $request): JsonResponse
    {
        $token = $request->get('token') ?? config('integrations.yandex.oauth_token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Токен не указан',
            ], 400);
        }

        $isValid = $this->oauthService->validateToken($token);
        $info = $isValid ? $this->oauthService->getTokenInfo($token) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => $isValid,
                'token_info' => $info,
            ],
        ]);
    }
}

