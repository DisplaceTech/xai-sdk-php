<?php

/**
 * This file is part of the xAI PHP SDK.
 *
 * (c) 2026 Displace Technologies, LLC
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * This work was inspired by X.AI LLC's Python SDK.
 */

declare(strict_types=1);

namespace Displace\XaiSdk\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Middleware that retries failed requests.
 *
 * Implements exponential backoff with jitter for retrying failed requests.
 * Respects Retry-After headers when present.
 *
 * @example
 * ```php
 * $middleware = new RetryMiddleware(
 *     maxRetries: 3,
 *     retryStatusCodes: [429, 500, 502, 503, 504],
 * );
 * ```
 */
final class RetryMiddleware implements MiddlewareInterface
{
    /**
     * Default status codes that trigger retries.
     *
     * @var array<int>
     */
    private const DEFAULT_RETRY_STATUS_CODES = [429, 500, 502, 503, 504];

    /**
     * Creates a new retry middleware.
     *
     * @param int $maxRetries Maximum number of retry attempts.
     * @param float $baseDelay Base delay in seconds for exponential backoff.
     * @param float $maxDelay Maximum delay in seconds.
     * @param float $multiplier Multiplier for exponential backoff.
     * @param array<int> $retryStatusCodes HTTP status codes that trigger retries.
     * @param LoggerInterface|null $logger Optional logger for retry events.
     */
    public function __construct(
        private readonly int $maxRetries = 3,
        private readonly float $baseDelay = 1.0,
        private readonly float $maxDelay = 30.0,
        private readonly float $multiplier = 2.0,
        private readonly array $retryStatusCodes = self::DEFAULT_RETRY_STATUS_CODES,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $attempt = 0;
        $response = null;

        while ($attempt <= $this->maxRetries) {
            $response = $next($request);

            if (! $this->shouldRetry($response, $attempt)) {
                return $response;
            }

            $delay = $this->calculateDelay($response, $attempt);

            $this->logger->info('Retrying request', [
                'attempt' => $attempt + 1,
                'max_retries' => $this->maxRetries,
                'delay_seconds' => $delay,
                'status_code' => $response->getStatusCode(),
                'uri' => (string) $request->getUri(),
            ]);

            usleep((int) ($delay * 1_000_000));
            $attempt++;
        }

        /** @var ResponseInterface $response */
        return $response;
    }

    /**
     * Determines if a request should be retried.
     *
     * @param ResponseInterface $response The response.
     * @param int $attempt Current attempt number.
     *
     * @return bool
     */
    private function shouldRetry(ResponseInterface $response, int $attempt): bool
    {
        if ($attempt >= $this->maxRetries) {
            return false;
        }

        return in_array($response->getStatusCode(), $this->retryStatusCodes, true);
    }

    /**
     * Calculates the delay before the next retry.
     *
     * @param ResponseInterface $response The response.
     * @param int $attempt Current attempt number.
     *
     * @return float Delay in seconds.
     */
    private function calculateDelay(ResponseInterface $response, int $attempt): float
    {
        // Check for Retry-After header
        if ($response->hasHeader('Retry-After')) {
            $retryAfter = $response->getHeaderLine('Retry-After');

            if (is_numeric($retryAfter)) {
                return min((float) $retryAfter, $this->maxDelay);
            }
        }

        // Exponential backoff with jitter
        $delay = $this->baseDelay * pow($this->multiplier, $attempt);
        $delay = min($delay, $this->maxDelay);

        // Add jitter (+-25%)
        $jitter = $delay * 0.25 * (mt_rand() / mt_getrandmax() * 2 - 1);

        return max(0.1, $delay + $jitter);
    }
}
