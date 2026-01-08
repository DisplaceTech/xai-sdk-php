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

namespace Displace\XaiSdk\Http;

/**
 * Configuration for HTTP client behavior.
 *
 * This class encapsulates all HTTP-related configuration including
 * timeouts, retry settings, and connection parameters.
 */
readonly class HttpConfig
{
    /**
     * Creates a new HTTP configuration.
     *
     * @param float $timeout Request timeout in seconds (default: 300)
     * @param float $connectTimeout Connection timeout in seconds (default: 10)
     * @param int $maxRetries Maximum number of retries for failed requests (default: 0, no retries)
     * @param float $retryDelay Base delay between retries in seconds (default: 1.0)
     * @param float $retryMultiplier Multiplier for exponential backoff (default: 2.0)
     * @param float $maxRetryDelay Maximum delay between retries in seconds (default: 30.0)
     * @param array<int> $retryStatusCodes HTTP status codes that trigger a retry (default: [429, 500, 502, 503, 504])
     * @param bool $retryOnConnectionError Whether to retry on connection errors (default: true)
     */
    public function __construct(
        public float $timeout = 300.0,
        public float $connectTimeout = 10.0,
        public int $maxRetries = 0,
        public float $retryDelay = 1.0,
        public float $retryMultiplier = 2.0,
        public float $maxRetryDelay = 30.0,
        public array $retryStatusCodes = [429, 500, 502, 503, 504],
        public bool $retryOnConnectionError = true,
    ) {}

    /**
     * Creates a configuration with default settings.
     *
     * @return self
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Creates a configuration with retry enabled.
     *
     * @param int $maxRetries Maximum number of retries
     * @return self
     */
    public static function withRetries(int $maxRetries = 3): self
    {
        return new self(maxRetries: $maxRetries);
    }

    /**
     * Creates a new configuration with modified timeout.
     *
     * @param float $timeout The new timeout in seconds
     * @return self
     */
    public function withTimeout(float $timeout): self
    {
        return new self(
            timeout: $timeout,
            connectTimeout: $this->connectTimeout,
            maxRetries: $this->maxRetries,
            retryDelay: $this->retryDelay,
            retryMultiplier: $this->retryMultiplier,
            maxRetryDelay: $this->maxRetryDelay,
            retryStatusCodes: $this->retryStatusCodes,
            retryOnConnectionError: $this->retryOnConnectionError,
        );
    }

    /**
     * Creates a new configuration with modified max retries.
     *
     * @param int $maxRetries The new max retries value
     * @return self
     */
    public function withMaxRetries(int $maxRetries): self
    {
        return new self(
            timeout: $this->timeout,
            connectTimeout: $this->connectTimeout,
            maxRetries: $maxRetries,
            retryDelay: $this->retryDelay,
            retryMultiplier: $this->retryMultiplier,
            maxRetryDelay: $this->maxRetryDelay,
            retryStatusCodes: $this->retryStatusCodes,
            retryOnConnectionError: $this->retryOnConnectionError,
        );
    }

    /**
     * Calculates the delay for a specific retry attempt.
     *
     * Uses exponential backoff with jitter.
     *
     * @param int $attempt The retry attempt number (0-based)
     * @return float The delay in seconds
     */
    public function calculateRetryDelay(int $attempt): float
    {
        $delay = $this->retryDelay * pow($this->retryMultiplier, $attempt);
        $delay = min($delay, $this->maxRetryDelay);

        // Add jitter (±25%)
        $jitter = $delay * 0.25 * (mt_rand() / mt_getrandmax() * 2 - 1);

        return max(0.1, $delay + $jitter);
    }

    /**
     * Checks if a status code should trigger a retry.
     *
     * @param int $statusCode The HTTP status code
     * @return bool
     */
    public function shouldRetryStatusCode(int $statusCode): bool
    {
        return in_array($statusCode, $this->retryStatusCodes, true);
    }

    /**
     * Checks if retries are enabled.
     *
     * @return bool
     */
    public function hasRetries(): bool
    {
        return $this->maxRetries > 0;
    }
}
