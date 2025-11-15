<?php

namespace App\Services\Http;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Guzzle Retry Middleware with exponential backoff + jitter
 * 
 * Retries on:
 * - 429 Too Many Requests
 * - 503 Service Unavailable
 * - 504 Gateway Timeout
 * - Connection errors
 * 
 * Backoff: base * (2 ^ attempt) + random jitter
 */
class GuzzleRetryMiddleware
{
    /**
     * Maximum retry attempts
     */
    private int $maxRetries;

    /**
     * Base delay in milliseconds (will multiply by exponential backoff)
     */
    private int $baseDelayMs;

    /**
     * Maximum delay in seconds (cap on backoff)
     */
    private int $maxDelaySeconds;

    /**
     * Jitter percentage (0-100)
     */
    private int $jitterPercent;

    /**
     * HTTP status codes to retry
     */
    private array $retryStatusCodes;

    /**
     * Constructor
     * 
     * @param int $maxRetries Default: 3
     * @param int $baseDelayMs Default: 100ms
     * @param int $maxDelaySeconds Default: 30s
     * @param int $jitterPercent Default: 25%
     */
    public function __construct(
        int $maxRetries = 3,
        int $baseDelayMs = 100,
        int $maxDelaySeconds = 30,
        int $jitterPercent = 25
    ) {
        $this->maxRetries = max(1, $maxRetries);
        $this->baseDelayMs = max(10, $baseDelayMs);
        $this->maxDelaySeconds = max(1, $maxDelaySeconds);
        $this->jitterPercent = max(0, min(100, $jitterPercent));

        // Status codes that should trigger retries
        $this->retryStatusCodes = [429, 503, 504];
    }

    /**
     * Guzzle middleware handler
     */
    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            return $this->retry($handler, $request, $options, 0);
        };
    }

    /**
     * Recursive retry logic
     */
    private function retry(
        callable $handler,
        RequestInterface $request,
        array $options,
        int $attempt
    ) {
        return $handler($request, $options)->then(
            function (ResponseInterface $response) use ($handler, $request, $options, $attempt) {
                return $this->handleResponse($response, $handler, $request, $options, $attempt);
            },
            function (\Exception $reason) use ($handler, $request, $options, $attempt) {
                return $this->handleException($reason, $handler, $request, $options, $attempt);
            }
        );
    }

    /**
     * Handle successful response (check for retryable status codes)
     */
    private function handleResponse(
        ResponseInterface $response,
        callable $handler,
        RequestInterface $request,
        array $options,
        int $attempt
    ) {
        $statusCode = $response->getStatusCode();

        if (in_array($statusCode, $this->retryStatusCodes) && $attempt < $this->maxRetries) {
            $delayMs = $this->calculateDelay($attempt);
            Log::warning('Retrying request due to status code', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'status' => $statusCode,
                'attempt' => $attempt + 1,
                'max_attempts' => $this->maxRetries,
                'delay_ms' => $delayMs,
            ]);

            usleep($delayMs * 1000); // Convert to microseconds
            return $this->retry($handler, $request, $options, $attempt + 1);
        }

        return $response;
    }

    /**
     * Handle request exceptions (connection errors, timeouts, etc.)
     */
    private function handleException(
        \Exception $reason,
        callable $handler,
        RequestInterface $request,
        array $options,
        int $attempt
    ) {
        $isRetryable = $this->isRetryableException($reason);

        if ($isRetryable && $attempt < $this->maxRetries) {
            $delayMs = $this->calculateDelay($attempt);
            Log::warning('Retrying request due to exception', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'exception' => get_class($reason),
                'message' => $reason->getMessage(),
                'attempt' => $attempt + 1,
                'max_attempts' => $this->maxRetries,
                'delay_ms' => $delayMs,
            ]);

            usleep($delayMs * 1000);
            return $this->retry($handler, $request, $options, $attempt + 1);
        }

        return new RejectedPromise($reason);
    }

    /**
     * Determine if exception is retryable
     */
    private function isRetryableException(\Exception $reason): bool
    {
        // Connection errors
        if ($reason instanceof ConnectException) {
            return true;
        }

        // Request exceptions with retryable status codes
        if ($reason instanceof RequestException && $reason->hasResponse()) {
            $statusCode = $reason->getResponse()->getStatusCode();
            return in_array($statusCode, $this->retryStatusCodes);
        }

        // Timeout errors (but allow specific timeout exceptions)
        if (strpos($reason->getMessage(), 'timeout') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Calculate exponential backoff with jitter
     * 
     * Formula: base * (2 ^ attempt) + random jitter
     * 
     * @param int $attempt 0-based attempt number
     * @return int Delay in milliseconds
     */
    private function calculateDelay(int $attempt): int
    {
        // Exponential backoff: 100ms * 2^attempt
        $exponentialMs = $this->baseDelayMs * pow(2, $attempt);

        // Cap at max delay
        $maxDelayMs = $this->maxDelaySeconds * 1000;
        $exponentialMs = min($exponentialMs, $maxDelayMs);

        // Add jitter: ±jitterPercent of exponential value
        $jitterRangeMs = ($exponentialMs / 100) * $this->jitterPercent;
        $jitterMs = random_int(0, (int) $jitterRangeMs);

        // Randomly add or subtract jitter (50% chance)
        if (random_int(0, 1)) {
            $delayMs = $exponentialMs + $jitterMs;
        } else {
            $delayMs = max($this->baseDelayMs, $exponentialMs - $jitterMs);
        }

        return (int) $delayMs;
    }
}
