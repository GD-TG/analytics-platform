<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Yandex\YandexOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private YandexOAuthService $yandexOAuth;

    public function __construct(YandexOAuthService $yandexOAuth)
    {
        $this->yandexOAuth = $yandexOAuth;
    }
    /**
     * Регистрация нового пользователя
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'user',
                'is_active' => true,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'avatar' => $user->avatar,
                    ],
                    'token' => $token,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при регистрации: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Авторизация пользователя
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный email или пароль',
                    'errors' => [
                        'email' => ['Неверный email или пароль'],
                    ],
                ], 422);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ваш аккаунт неактивен',
                    'errors' => [
                        'email' => ['Ваш аккаунт неактивен'],
                    ],
                ], 422);
            }

            // Удаляем все существующие токены пользователя
            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'avatar' => $user->avatar,
                    ],
                    'token' => $token,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при входе: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Выход пользователя
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Получить текущего пользователя
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ],
            ],
        ]);
    }

    /**
     * Авторизация через Yandex ID
     */
    public function yandexAuth(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'redirect_uri' => 'required|string',
            ]);

            $code = $request->get('code');
            $redirectUri = $request->get('redirect_uri');

            // Обмениваем код на токен
            $tokenData = $this->yandexOAuth->exchangeUserCode($code, $redirectUri);

            if (!$tokenData || !isset($tokenData['access_token'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить токен от Yandex',
                ], 400);
            }

            // Получаем информацию о пользователе
            $yandexUserInfo = $this->yandexOAuth->getUserInfo($tokenData['access_token']);

            if (!$yandexUserInfo || !$yandexUserInfo['yandex_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить информацию о пользователе',
                ], 400);
            }

            // Ищем пользователя по yandex_id
            $user = User::where('yandex_id', $yandexUserInfo['yandex_id'])->first();
            
            // Если не найден по yandex_id, ищем по email (только если у него нет yandex_id)
            if (!$user && $yandexUserInfo['email']) {
                $user = User::where('email', $yandexUserInfo['email'])
                    ->whereNull('yandex_id')
                    ->first();
            }

            if ($user) {
                // Обновляем информацию о пользователе
                $user->update([
                    'yandex_id' => $yandexUserInfo['yandex_id'],
                    'email' => $yandexUserInfo['email'],
                    'name' => $yandexUserInfo['name'],
                    'first_name' => $yandexUserInfo['first_name'],
                    'last_name' => $yandexUserInfo['last_name'],
                    'avatar' => $yandexUserInfo['avatar'] ?? $user->avatar,
                ]);
            } else {
                // Создаем нового пользователя
                $user = User::create([
                    'yandex_id' => $yandexUserInfo['yandex_id'],
                    'email' => $yandexUserInfo['email'],
                    'name' => $yandexUserInfo['name'],
                    'first_name' => $yandexUserInfo['first_name'],
                    'last_name' => $yandexUserInfo['last_name'],
                    'avatar' => $yandexUserInfo['avatar'],
                    'password' => null, // Пароль не нужен для Yandex авторизации
                    'role' => 'user',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            }

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ваш аккаунт неактивен',
                ], 403);
            }

            // Удаляем все существующие токены
            $user->tokens()->delete();

            // Создаем новый токен
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Авторизация через Yandex успешна',
                'data' => [
                    'user' => $user->only(['id', 'name', 'first_name', 'last_name', 'email', 'role', 'avatar']),
                    'token' => $token,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при авторизации через Yandex: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить URL для авторизации через Yandex
     */
    public function getYandexAuthUrl(Request $request): JsonResponse
    {
        $redirectUri = $request->get('redirect_uri', url('/auth/yandex/callback'));
        $url = $this->yandexOAuth->getUserAuthorizationUrl($redirectUri, ['login:email', 'login:info']);

        return response()->json([
            'success' => true,
            'data' => [
                'auth_url' => $url,
            ],
        ]);
    }
}
