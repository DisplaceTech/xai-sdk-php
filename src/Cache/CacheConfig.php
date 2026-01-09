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
 * Configuration for response caching.
 *
 * Allows fine-grained control over which requests are cached
 * and for how long.
 *
 * @example
 * ```php
 * $config = new CacheConfig(
 *     enabled: true,
 *     defaultTtl: 3600,
 *     cacheableEndpoints: ['/chat/completions', '/models'],
 * );
 * ```
 */
readonly class CacheConfig
{
    /**
     * Default cache endpoints that are safe to cache.
     *
     * @var array<string>
     */
    public const DEFAULT_CACHEABLE_ENDPOINTS = [
        '/models',
        '/tokenize',
    ];

    /**
     * Creates a new cache configuration.
     *
     * @param bool $enabled Whether caching is enabled.
     * @param int $defaultTtl Default TTL in seconds.
     * @param array<string> $cacheableEndpoints Endpoints that can be cached.
     * @param bool $cacheStreaming Whether to cache streaming responses (usually false).
     * @param bool $requireSeed Only cache requests with a seed parameter (for determinism).
     * @param array<string, int> $endpointTtls Custom TTLs for specific endpoints.
     * @param string $keyPrefix Prefix for cache keys (for namespacing).
     */
    public function __construct(
        public bool $enabled = true,
        public int $defaultTtl = 3600,
        public array $cacheableEndpoints = self::DEFAULT_CACHEABLE_ENDPOINTS,
        public bool $cacheStreaming = false,
        public bool $requireSeed = false,
        public array $endpointTtls = [],
        public string $keyPrefix = 'xai_sdk_',
    ) {
    }

    /**
     * Creates a disabled cache configuration.
     *
     * @return self
     */
    public static function disabled(): self
    {
        return new self(enabled: false);
    }

    /**
     * Creates a configuration that caches only deterministic requests.
     *
     * Deterministic requests include those with a seed parameter,
     * which should always return the same result.
     *
     * @param int $ttl TTL for cached responses.
     *
     * @return self
     */
    public static function deterministicOnly(int $ttl = 86400): self
    {
        return new self(
            enabled: true,
            defaultTtl: $ttl,
            cacheableEndpoints: ['/chat/completions'],
            requireSeed: true,
        );
    }

    /**
     * Creates a configuration that caches model and tokenizer responses.
     *
     * These endpoints return relatively static data that's safe to cache.
     *
     * @param int $ttl TTL for cached responses.
     *
     * @return self
     */
    public static function metadataOnly(int $ttl = 86400): self
    {
        return new self(
            enabled: true,
            defaultTtl: $ttl,
            cacheableEndpoints: ['/models', '/tokenize'],
        );
    }

    /**
     * Checks if an endpoint is cacheable.
     *
     * @param string $endpoint The endpoint path.
     *
     * @return bool
     */
    public function isCacheableEndpoint(string $endpoint): bool
    {
        if (! $this->enabled) {
            return false;
        }

        foreach ($this->cacheableEndpoints as $cacheableEndpoint) {
            if (str_starts_with($endpoint, $cacheableEndpoint)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the TTL for an endpoint.
     *
     * @param string $endpoint The endpoint path.
     *
     * @return int TTL in seconds.
     */
    public function getTtlForEndpoint(string $endpoint): int
    {
        foreach ($this->endpointTtls as $pattern => $ttl) {
            if (str_starts_with($endpoint, $pattern)) {
                return $ttl;
            }
        }

        return $this->defaultTtl;
    }

    /**
     * Creates a new configuration with modified TTL.
     *
     * @param int $ttl New default TTL.
     *
     * @return self
     */
    public function withTtl(int $ttl): self
    {
        return new self(
            enabled: $this->enabled,
            defaultTtl: $ttl,
            cacheableEndpoints: $this->cacheableEndpoints,
            cacheStreaming: $this->cacheStreaming,
            requireSeed: $this->requireSeed,
            endpointTtls: $this->endpointTtls,
            keyPrefix: $this->keyPrefix,
        );
    }

    /**
     * Creates a new configuration with additional cacheable endpoints.
     *
     * @param array<string> $endpoints Additional endpoints to cache.
     *
     * @return self
     */
    public function withEndpoints(array $endpoints): self
    {
        return new self(
            enabled: $this->enabled,
            defaultTtl: $this->defaultTtl,
            cacheableEndpoints: array_merge($this->cacheableEndpoints, $endpoints),
            cacheStreaming: $this->cacheStreaming,
            requireSeed: $this->requireSeed,
            endpointTtls: $this->endpointTtls,
            keyPrefix: $this->keyPrefix,
        );
    }
}
