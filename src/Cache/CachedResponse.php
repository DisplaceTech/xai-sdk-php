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

namespace Displace\XaiSdk\Cache;

/**
 * Represents a cached API response.
 *
 * Contains the response data along with cache metadata such as
 * when it was cached and the TTL.
 */
readonly class CachedResponse
{
    /**
     * Creates a new cached response.
     *
     * @param int $statusCode The HTTP status code.
     * @param array<string, mixed> $body The response body.
     * @param int $cachedAt Unix timestamp when the response was cached.
     * @param int $ttl Time-to-live in seconds.
     */
    public function __construct(
        public int $statusCode,
        public array $body,
        public int $cachedAt,
        public int $ttl,
    ) {
    }

    /**
     * Creates a CachedResponse from an array.
     *
     * @param array<string, mixed> $data The array data.
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            statusCode: $data['status_code'] ?? 200,
            body: $data['body'] ?? [],
            cachedAt: $data['cached_at'] ?? time(),
            ttl: $data['ttl'] ?? 0,
        );
    }

    /**
     * Converts the cached response to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status_code' => $this->statusCode,
            'body' => $this->body,
            'cached_at' => $this->cachedAt,
            'ttl' => $this->ttl,
        ];
    }

    /**
     * Checks if the cached response has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return time() > ($this->cachedAt + $this->ttl);
    }

    /**
     * Gets the remaining TTL in seconds.
     *
     * @return int Remaining seconds, or 0 if expired.
     */
    public function getRemainingTtl(): int
    {
        $remaining = ($this->cachedAt + $this->ttl) - time();

        return max(0, $remaining);
    }

    /**
     * Gets the age of the cached response in seconds.
     *
     * @return int
     */
    public function getAge(): int
    {
        return time() - $this->cachedAt;
    }
}
