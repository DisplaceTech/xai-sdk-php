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

use Psr\SimpleCache\CacheInterface as SimpleCacheInterface;

/**
 * Response cache supporting PSR-16 (Simple Cache).
 *
 * Provides caching for API responses to reduce latency and API usage
 * for deterministic requests.
 *
 * @example
 * ```php
 * use Symfony\Component\Cache\Psr16Cache;
 * use Symfony\Component\Cache\Adapter\FilesystemAdapter;
 *
 * $cache = new Psr16Cache(new FilesystemAdapter());
 * $responseCache = new ResponseCache($cache);
 *
 * // Check for cached response
 * if ($cached = $responseCache->get($request, $body)) {
 *     return $cached;
 * }
 *
 * // Make request and cache result
 * $response = $client->send($request);
 * $responseCache->set($request, $body, $response);
 * ```
 */
final class ResponseCache
{
    /**
     * Creates a new response cache.
     *
     * @param SimpleCacheInterface $cache The PSR-16 cache implementation.
     * @param CacheConfig $config Cache configuration.
     */
    public function __construct(
        private readonly SimpleCacheInterface $cache,
        private readonly CacheConfig $config = new CacheConfig(),
    ) {
    }

    /**
     * Gets a cached response.
     *
     * @param string $method The HTTP method.
     * @param string $endpoint The API endpoint.
     * @param array<string, mixed> $body The request body.
     * @param array<string, string> $headers The request headers (optional, for varying).
     *
     * @return CachedResponse|null The cached response, or null if not cached.
     */
    public function get(
        string $method,
        string $endpoint,
        array $body = [],
        array $headers = [],
    ): ?CachedResponse {
        if (! $this->shouldCache($method, $endpoint, $body)) {
            return null;
        }

        $key = $this->generateKey($method, $endpoint, $body, $headers);
        $cached = $this->cache->get($key);

        if ($cached === null) {
            return null;
        }

        if (! is_array($cached)) {
            return null;
        }

        return CachedResponse::fromArray($cached);
    }

    /**
     * Stores a response in the cache.
     *
     * @param string $method The HTTP method.
     * @param string $endpoint The API endpoint.
     * @param array<string, mixed> $body The request body.
     * @param int $statusCode The response status code.
     * @param array<string, mixed> $responseBody The response body.
     * @param array<string, string> $headers The request headers (for varying).
     * @param int|null $ttl Custom TTL (uses config default if null).
     *
     * @return bool Whether the cache operation succeeded.
     */
    public function set(
        string $method,
        string $endpoint,
        array $body,
        int $statusCode,
        array $responseBody,
        array $headers = [],
        ?int $ttl = null,
    ): bool {
        if (! $this->shouldCache($method, $endpoint, $body)) {
            return false;
        }

        // Don't cache error responses
        if ($statusCode >= 400) {
            return false;
        }

        $key = $this->generateKey($method, $endpoint, $body, $headers);
        $ttl ??= $this->config->getTtlForEndpoint($endpoint);

        $cached = new CachedResponse(
            statusCode: $statusCode,
            body: $responseBody,
            cachedAt: time(),
            ttl: $ttl,
        );

        return $this->cache->set($key, $cached->toArray(), $ttl);
    }

    /**
     * Removes a cached response.
     *
     * @param string $method The HTTP method.
     * @param string $endpoint The API endpoint.
     * @param array<string, mixed> $body The request body.
     * @param array<string, string> $headers The request headers.
     *
     * @return bool Whether the deletion succeeded.
     */
    public function delete(
        string $method,
        string $endpoint,
        array $body = [],
        array $headers = [],
    ): bool {
        $key = $this->generateKey($method, $endpoint, $body, $headers);

        return $this->cache->delete($key);
    }

    /**
     * Checks if a response exists in cache.
     *
     * @param string $method The HTTP method.
     * @param string $endpoint The API endpoint.
     * @param array<string, mixed> $body The request body.
     * @param array<string, string> $headers The request headers.
     *
     * @return bool
     */
    public function has(
        string $method,
        string $endpoint,
        array $body = [],
        array $headers = [],
    ): bool {
        $key = $this->generateKey($method, $endpoint, $body, $headers);

        return $this->cache->has($key);
    }

    /**
     * Clears all cached responses.
     *
     * @return bool Whether the clear operation succeeded.
     */
    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * Determines if a request should be cached.
     *
     * @param string $method The HTTP method.
     * @param string $endpoint The API endpoint.
     * @param array<string, mixed> $body The request body.
     *
     * @return bool
     */
    public function shouldCache(string $method, string $endpoint, array $body = []): bool
    {
        // Only cache safe methods
        if (! in_array(strtoupper($method), ['GET', 'POST'], true)) {
            return false;
        }

        // Check if streaming
        if (isset($body['stream']) && $body['stream'] === true && ! $this->config->cacheStreaming) {
            return false;
        }

        // Check if endpoint is cacheable
        if (! $this->config->isCacheableEndpoint($endpoint)) {
            return false;
        }

        // If requireSeed is enabled, only cache requests with a seed
        return ! ($this->config->requireSeed && ! isset($body['seed']))

        ;
    }

    /**
     * Generates a cache key for a request.
     *
     * @param string $method The HTTP method.
     * @param string $endpoint The API endpoint.
     * @param array<string, mixed> $body The request body.
     * @param array<string, string> $headers Headers to include in the key.
     *
     * @return string
     */
    public function generateKey(
        string $method,
        string $endpoint,
        array $body = [],
        array $headers = [],
    ): string {
        // Create a normalized representation for hashing
        $data = [
            'method' => strtoupper($method),
            'endpoint' => $endpoint,
            'body' => $this->normalizeForHashing($body),
        ];

        // Include relevant headers in the cache key
        $relevantHeaders = array_intersect_key(
            $headers,
            array_flip(['Accept', 'Content-Type']),
        );

        if ($relevantHeaders !== []) {
            ksort($relevantHeaders);
            $data['headers'] = $relevantHeaders;
        }

        $hash = hash('xxh3', json_encode($data, JSON_THROW_ON_ERROR));

        return $this->config->keyPrefix . $hash;
    }

    /**
     * Gets the cache configuration.
     *
     * @return CacheConfig
     */
    public function getConfig(): CacheConfig
    {
        return $this->config;
    }

    /**
     * Creates a new cache with different configuration.
     *
     * @param CacheConfig $config The new configuration.
     *
     * @return self
     */
    public function withConfig(CacheConfig $config): self
    {
        return new self($this->cache, $config);
    }

    /**
     * Normalizes data for consistent hashing.
     *
     * @param array<string, mixed> $data The data to normalize.
     *
     * @return array<string, mixed>
     */
    private function normalizeForHashing(array $data): array
    {
        // Sort keys recursively
        ksort($data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalizeForHashing($value);
            }
        }

        return $data;
    }
}
