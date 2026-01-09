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

use Displace\XaiSdk\Cache\CacheConfig;
use Displace\XaiSdk\Cache\ResponseCache;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Middleware that caches API responses.
 *
 * Uses the ResponseCache to cache deterministic requests and
 * return cached responses when available.
 *
 * @example
 * ```php
 * use Symfony\Component\Cache\Psr16Cache;
 * use Symfony\Component\Cache\Adapter\FilesystemAdapter;
 *
 * $cache = new Psr16Cache(new FilesystemAdapter());
 * $middleware = new CachingMiddleware($cache, new CacheConfig(
 *     cacheableEndpoints: ['/chat/completions'],
 *     requireSeed: true,
 * ));
 *
 * $client->getHttpClient()->withMiddleware($middleware);
 * ```
 */
final class CachingMiddleware implements MiddlewareInterface
{
    private readonly ResponseCache $responseCache;

    /**
     * Creates a new caching middleware.
     *
     * @param CacheInterface $cache The PSR-16 cache implementation.
     * @param CacheConfig|null $config Cache configuration.
     */
    public function __construct(
        CacheInterface $cache,
        ?CacheConfig $config = null,
    ) {
        $this->responseCache = new ResponseCache($cache, $config ?? new CacheConfig());
    }

    /**
     * {@inheritDoc}
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $method = $request->getMethod();
        $endpoint = $request->getUri()->getPath();
        $body = $this->parseRequestBody($request);
        $headers = $this->getRelevantHeaders($request);

        // Check if we should cache this request
        if (! $this->responseCache->shouldCache($method, $endpoint, $body)) {
            return $next($request);
        }

        // Try to get from cache
        $cached = $this->responseCache->get($method, $endpoint, $body, $headers);

        if ($cached !== null && ! $cached->isExpired()) {
            return new Response(
                $cached->statusCode,
                [
                    'Content-Type' => 'application/json',
                    'X-Cache' => 'HIT',
                    'X-Cache-Age' => (string) $cached->getAge(),
                ],
                json_encode($cached->body, JSON_THROW_ON_ERROR),
            );
        }

        // Execute request
        $response = $next($request);

        // Cache successful responses
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $responseBody = $this->parseResponseBody($response);

            if ($responseBody !== null) {
                $this->responseCache->set(
                    $method,
                    $endpoint,
                    $body,
                    $response->getStatusCode(),
                    $responseBody,
                    $headers,
                );

                // Add cache miss header
                $response = $response->withHeader('X-Cache', 'MISS');
            }
        }

        return $response;
    }

    /**
     * Gets the response cache.
     *
     * @return ResponseCache
     */
    public function getResponseCache(): ResponseCache
    {
        return $this->responseCache;
    }

    /**
     * Parses the request body.
     *
     * @param RequestInterface $request The request.
     *
     * @return array<string, mixed>
     */
    private function parseRequestBody(RequestInterface $request): array
    {
        $body = (string) $request->getBody();
        $request->getBody()->rewind();

        if ($body === '') {
            return [];
        }

        $parsed = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($parsed)) {
            return [];
        }

        return $parsed;
    }

    /**
     * Parses the response body.
     *
     * @param ResponseInterface $response The response.
     *
     * @return array<string, mixed>|null
     */
    private function parseResponseBody(ResponseInterface $response): ?array
    {
        $body = (string) $response->getBody();
        $response->getBody()->rewind();

        if ($body === '') {
            return null;
        }

        $parsed = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($parsed)) {
            return null;
        }

        return $parsed;
    }

    /**
     * Gets headers relevant for caching.
     *
     * @param RequestInterface $request The request.
     *
     * @return array<string, string>
     */
    private function getRelevantHeaders(RequestInterface $request): array
    {
        $relevant = [];

        foreach (['Accept', 'Content-Type'] as $header) {
            if ($request->hasHeader($header)) {
                $relevant[$header] = $request->getHeaderLine($header);
            }
        }

        return $relevant;
    }
}
