<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    /**
     * Get user settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();

        // Get settings from user preferences or config
        $settings = [
            'user_id' => $userId,
            'email' => $user->email,
            'name' => $user->name ?? '',
            'integrations' => [
                'yandex_metrika' => [
                    'client_id' => $this->getMaskedValue($user->yandex_metrika_client_id ?? ''),
                    'client_secret' => $this->getMaskedValue($user->yandex_metrika_client_secret ?? ''),
                    'configured' => !empty($user->yandex_metrika_client_id),
                ],
                'yandex_direct' => [
                    'client_id' => $this->getMaskedValue($user->yandex_direct_client_id ?? ''),
                    'client_secret' => $this->getMaskedValue($user->yandex_direct_client_secret ?? ''),
                    'configured' => !empty($user->yandex_direct_client_id),
                ],
            ],
            'sync' => [
                'interval_minutes' => $user->sync_interval_minutes ?? 60,
                'enabled' => $user->sync_enabled ?? true,
            ],
        ];

        return response()->json($settings);
    }

    /**
     * Update Yandex Metrika settings
     */
    public function updateYandexMetrika(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'client_id' => 'required|string|min:10',
            'client_secret' => 'required|string|min:10',
        ], [
            'client_id.required' => 'Client ID is required',
            'client_id.min' => 'Client ID must be at least 10 characters',
            'client_secret.required' => 'Client Secret is required',
            'client_secret.min' => 'Client Secret must be at least 10 characters',
        ]);

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->update([
                'yandex_metrika_client_id' => $validated['client_id'],
                'yandex_metrika_client_secret' => $validated['client_secret'],
            ]);

            Log::info('Yandex Metrika settings updated', [
                'user_id' => $userId,
                'client_id' => substr($validated['client_id'], 0, 5) . '...',
            ]);

            return response()->json([
                'message' => 'Yandex Metrika settings updated successfully',
                'configured' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update Yandex Metrika settings', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Yandex Direct settings
     */
    public function updateYandexDirect(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'client_id' => 'required|string|min:10',
            'client_secret' => 'required|string|min:10',
        ], [
            'client_id.required' => 'Client ID is required',
            'client_id.min' => 'Client ID must be at least 10 characters',
            'client_secret.required' => 'Client Secret is required',
            'client_secret.min' => 'Client Secret must be at least 10 characters',
        ]);

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->update([
                'yandex_direct_client_id' => $validated['client_id'],
                'yandex_direct_client_secret' => $validated['client_secret'],
            ]);

            Log::info('Yandex Direct settings updated', [
                'user_id' => $userId,
                'client_id' => substr($validated['client_id'], 0, 5) . '...',
            ]);

            return response()->json([
                'message' => 'Yandex Direct settings updated successfully',
                'configured' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update Yandex Direct settings', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update sync settings
     */
    public function updateSyncSettings(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'interval_minutes' => 'required|integer|min:5|max:1440',
            'enabled' => 'required|boolean',
        ], [
            'interval_minutes.min' => 'Interval must be at least 5 minutes',
            'interval_minutes.max' => 'Interval cannot exceed 24 hours (1440 minutes)',
        ]);

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->update([
                'sync_interval_minutes' => $validated['interval_minutes'],
                'sync_enabled' => $validated['enabled'],
            ]);

            Log::info('Sync settings updated', [
                'user_id' => $userId,
                'interval_minutes' => $validated['interval_minutes'],
                'enabled' => $validated['enabled'],
            ]);

            return response()->json([
                'message' => 'Sync settings updated successfully',
                'interval_minutes' => $validated['interval_minutes'],
                'enabled' => $validated['enabled'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update sync settings', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test Yandex Metrika credentials
     */
    public function testYandexMetrika(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $user = Auth::user();
            $clientId = $user->yandex_metrika_client_id;
            $clientSecret = $user->yandex_metrika_client_secret;

            if (!$clientId || !$clientSecret) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Credentials not configured',
                ], 400);
            }

            // Test by making a request to Yandex API
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://oauth.yandex.com/token', [
                'form_params' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['error'])) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invalid credentials: ' . ($body['error_description'] ?? $body['error']),
                ]);
            }

            return response()->json([
                'valid' => true,
                'message' => 'Yandex Metrika credentials are valid',
            ]);
        } catch (\Exception $e) {
            Log::warning('Yandex Metrika credential test failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Test Yandex Direct credentials
     */
    public function testYandexDirect(Request $request): JsonResponse
    {
        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $user = Auth::user();
            $clientId = $user->yandex_direct_client_id;
            $clientSecret = $user->yandex_direct_client_secret;

            if (!$clientId || !$clientSecret) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Credentials not configured',
                ], 400);
            }

            // Test by making a request to Yandex Direct API
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.direct.yandex.com/v4/agencyclients', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $clientId,
                    'Accept-Language' => 'en',
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                return response()->json([
                    'valid' => true,
                    'message' => 'Yandex Direct credentials are valid',
                ]);
            }

            return response()->json([
                'valid' => false,
                'message' => 'Invalid credentials (HTTP ' . $response->getStatusCode() . ')',
            ]);
        } catch (\Exception $e) {
            Log::warning('Yandex Direct credential test failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'valid' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mask sensitive values for display
     */
    private function getMaskedValue(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        if (strlen($value) <= 4) {
            return '****';
        }

        return substr($value, 0, 4) . '****' . substr($value, -2);
    }
}
