<?php

namespace App\Services\Http;

use App\Services\RateLimiting\ApiRateLimiter;
use GuzzleHttp\Promise\RejectedPromise;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;

/**
 * Guzzle Rate Limit Middleware
 * 
 * Enforces per-account rate limiting before making API requests.
 * When limit is exceeded, request is rejected and returned to queue.
 */
class GuzzleRateLimitMiddleware
{
    /**
     * Rate limiter instance
     */
    private ApiRateLimiter $rateLimiter;

    /**
     * Account ID extractor function
     * Maps request to account ID for rate limiting
     */
    private ?\Closure $accountIdExtractor;

    /**
     * Constructor
     * 
     * @param ApiRateLimiter $rateLimiter Rate limiter instance
     * @param \Closure|null $accountIdExtractor Function to extract account ID from request
     */
    public function __construct(
        ApiRateLimiter $rateLimiter,
        ?\Closure $accountIdExtractor = null
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->accountIdExtractor = $accountIdExtractor ?? $this->defaultAccountIdExtractor();
    }

    /**
     * Guzzle middleware handler
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            // Extract account ID from request (from options or URI)
            $accountId = ($this->accountIdExtractor)($request, $options);

            if (!$accountId) {
                // No rate limit if account not identified
                return $handler($request, $options);
            }

            // Check rate limit
            if (!$this->rateLimiter->allow($accountId, 1)) {
                // Rate limited - return error response
                $retryAfter = $this->rateLimiter->getRetryAfterSeconds(
                    $this->getStatusData($accountId)['tokens_used'] ?? 0,
                    1,
                    60 // default limit
                );

                Log::warning('Request rejected due to rate limit', [
                    'account_id' => $accountId,
                    'method' => $request->getMethod(),
                    'uri' => (string) $request->getUri(),
                    'retry_after_seconds' => $retryAfter,
                ]);

                // Return rejected promise with 429 error
                return new RejectedPromise(
                    new \Exception(
                        "Rate limited: retry after {$retryAfter}s",
                        429
                    )
                );
            }

            // Proceed with request
            return $handler($request, $options);
        };
    }

    /**
     * Default account ID extractor
     * Looks for 'account_id' in request options
     */
    private function defaultAccountIdExtractor(): \Closure
    {
        return function (RequestInterface $request, array $options) {
            return $options['account_id'] ?? $options['yandex_account_id'] ?? null;
        };
    }

    /**
     * Get rate limit status (helper)
     */
    private function getStatusData($accountId): array
    {
        return $this->rateLimiter->getStatus($accountId);
    }
}
