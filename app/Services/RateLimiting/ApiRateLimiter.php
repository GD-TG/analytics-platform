<?php

namespace App\Services\RateLimiting;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Rate Limiter using Redis with Leaky Bucket algorithm
 * 
 * Per-account rate limiting for API calls.
 * When limit is exceeded, returns false - caller should queue for later retry.
 * 
 * Algorithm: Leaky Bucket
 * - Each request "fills" the bucket by increment
 * - Bucket "leaks" over time (exponential decay)
 * - When bucket > limit, request is rejected
 */
class ApiRateLimiter
{
    /**
     * Store key prefix
     */
    private const RATE_LIMIT_PREFIX = 'rate_limit:';

    /**
     * Default limit: requests per minute
     */
    private int $requestsPerMinute;

    /**
     * Redis cache store
     */
    private string $store;

    /**
     * Constructor
     * 
     * @param int $requestsPerMinute Default: 60 requests/min (1 per second)
     * @param string $store Cache store name (must support TTL)
     */
    public function __construct(int $requestsPerMinute = 60, string $store = 'redis')
    {
        $this->requestsPerMinute = max(1, $requestsPerMinute);
        $this->store = $store;
    }

    /**
     * Check if request is allowed for account
     * 
     * @param int|string $accountId Yandex account ID
     * @param int $tokens Tokens to consume (default 1)
     * @return bool true if allowed, false if rate limited
     */
    public function allow($accountId, int $tokens = 1): bool
    {
        $key = $this->getKey($accountId);
        $now = time();
        $limit = $this->requestsPerMinute;
        $windowMs = 60 * 1000; // 1 minute in milliseconds

        try {
            // Get current bucket state
            $data = Cache::store($this->store)->get($key);
            
            if (!$data) {
                // First request - initialize bucket
                Cache::store($this->store)->put(
                    $key,
                    json_encode(['tokens' => 0, 'last_refill' => $now * 1000]),
                    60 // TTL: 1 minute
                );
                return true;
            }

            $bucket = json_decode($data, true);
            $lastRefillMs = $bucket['last_refill'] ?? ($now * 1000);
            $elapsedMs = ($now * 1000) - $lastRefillMs;

            // Leak: remove tokens based on time elapsed
            // Rate: limit tokens per 60 seconds = limit/60000 tokens per millisecond
            $tokensToLeak = ($elapsedMs / 60000) * $limit;
            $currentTokens = max(0, ($bucket['tokens'] ?? 0) - $tokensToLeak);

            // Try to consume tokens
            if ($currentTokens + $tokens > $limit) {
                // Rate limited
                Log::warning('Rate limit exceeded for account', [
                    'account_id' => $accountId,
                    'current_tokens' => $currentTokens,
                    'requested_tokens' => $tokens,
                    'limit' => $limit,
                    'retry_after_seconds' => $this->getRetryAfterSeconds($currentTokens, $tokens, $limit),
                ]);

                return false;
            }

            // Update bucket
            $newTokens = $currentTokens + $tokens;
            Cache::store($this->store)->put(
                $key,
                json_encode(['tokens' => $newTokens, 'last_refill' => $now * 1000]),
                60
            );

            return true;
        } catch (\Exception $e) {
            // On error, allow request (don't break service)
            Log::error('Rate limiter error', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return true;
        }
    }

    /**
     * Get how long to wait before retrying (in seconds)
     * 
     * @param float $currentTokens Current tokens in bucket
     * @param int $requestedTokens Tokens needed for request
     * @param int $limit Token limit
     * @return int Seconds to wait
     */
    public function getRetryAfterSeconds(float $currentTokens, int $requestedTokens, int $limit): int
    {
        $tokensNeeded = ($currentTokens + $requestedTokens) - $limit;
        // Time to leak needed tokens at limit tokens/60s rate
        $secondsToWait = ceil(($tokensNeeded * 60) / $limit);
        return max(1, $secondsToWait);
    }

    /**
     * Reset rate limit for account (admin only)
     */
    public function reset($accountId): void
    {
        Cache::store($this->store)->forget($this->getKey($accountId));
        Log::info('Rate limit reset for account', ['account_id' => $accountId]);
    }

    /**
     * Get current rate limit status
     */
    public function getStatus($accountId): array
    {
        $key = $this->getKey($accountId);
        $data = Cache::store($this->store)->get($key);

        if (!$data) {
            return [
                'account_id' => $accountId,
                'tokens' => 0,
                'limit' => $this->requestsPerMinute,
                'available' => $this->requestsPerMinute,
            ];
        }

        $bucket = json_decode($data, true);
        $tokensUsed = $bucket['tokens'] ?? 0;
        $available = $this->requestsPerMinute - $tokensUsed;

        return [
            'account_id' => $accountId,
            'tokens_used' => $tokensUsed,
            'limit' => $this->requestsPerMinute,
            'available' => max(0, $available),
        ];
    }

    /**
     * Generate cache key
     */
    private function getKey($accountId): string
    {
        return self::RATE_LIMIT_PREFIX . $accountId;
    }
}
