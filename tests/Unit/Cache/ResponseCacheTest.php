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

namespace Displace\XaiSdk\Tests\Unit\Cache;

use Displace\XaiSdk\Cache\CachedResponse;
use Displace\XaiSdk\Cache\CacheConfig;
use Displace\XaiSdk\Cache\ResponseCache;
use Displace\XaiSdk\Tests\TestCase;
use Psr\SimpleCache\CacheInterface;

final class ResponseCacheTest extends TestCase
{
    private CacheInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new class implements CacheInterface {
            /** @var array<string, mixed> */
            private array $data = [];

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->data[$key] ?? $default;
            }

            public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
            {
                $this->data[$key] = $value;

                return true;
            }

            public function delete(string $key): bool
            {
                unset($this->data[$key]);

                return true;
            }

            public function clear(): bool
            {
                $this->data = [];

                return true;
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                $result = [];
                foreach ($keys as $key) {
                    $result[$key] = $this->get($key, $default);
                }

                return $result;
            }

            public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
            {
                foreach ($values as $key => $value) {
                    $this->set($key, $value, $ttl);
                }

                return true;
            }

            public function deleteMultiple(iterable $keys): bool
            {
                foreach ($keys as $key) {
                    $this->delete($key);
                }

                return true;
            }

            public function has(string $key): bool
            {
                return isset($this->data[$key]);
            }
        };
    }

    public function test_set_and_get(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            cacheableEndpoints: ['/models'],
        ));

        $success = $cache->set(
            'GET',
            '/models',
            [],
            200,
            ['data' => [['id' => 'grok-3']]],
        );

        $this->assertTrue($success);

        $cached = $cache->get('GET', '/models');

        $this->assertInstanceOf(CachedResponse::class, $cached);
        $this->assertSame(200, $cached->statusCode);
        $this->assertSame(['data' => [['id' => 'grok-3']]], $cached->body);
    }

    public function test_does_not_cache_non_cacheable_endpoints(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            cacheableEndpoints: ['/models'],
        ));

        $success = $cache->set(
            'POST',
            '/chat/completions',
            [],
            200,
            ['result' => 'test'],
        );

        $this->assertFalse($success);
    }

    public function test_does_not_cache_error_responses(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            cacheableEndpoints: ['/models'],
        ));

        $success = $cache->set(
            'GET',
            '/models',
            [],
            500,
            ['error' => 'Server error'],
        );

        $this->assertFalse($success);
    }

    public function test_does_not_cache_streaming_by_default(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            cacheableEndpoints: ['/chat/completions'],
        ));

        $shouldCache = $cache->shouldCache(
            'POST',
            '/chat/completions',
            ['stream' => true],
        );

        $this->assertFalse($shouldCache);
    }

    public function test_caches_streaming_when_enabled(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            cacheableEndpoints: ['/chat/completions'],
            cacheStreaming: true,
        ));

        $shouldCache = $cache->shouldCache(
            'POST',
            '/chat/completions',
            ['stream' => true],
        );

        $this->assertTrue($shouldCache);
    }

    public function test_require_seed(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            cacheableEndpoints: ['/chat/completions'],
            requireSeed: true,
        ));

        $withoutSeed = $cache->shouldCache('POST', '/chat/completions', []);
        $withSeed = $cache->shouldCache('POST', '/chat/completions', ['seed' => 42]);

        $this->assertFalse($withoutSeed);
        $this->assertTrue($withSeed);
    }

    public function test_delete(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            cacheableEndpoints: ['/models'],
        ));

        $cache->set('GET', '/models', [], 200, ['data' => []]);

        $this->assertTrue($cache->has('GET', '/models'));

        $cache->delete('GET', '/models');

        $this->assertFalse($cache->has('GET', '/models'));
    }

    public function test_clear(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            cacheableEndpoints: ['/models', '/tokenize'],
        ));

        $cache->set('GET', '/models', [], 200, ['data' => []]);
        $cache->set('POST', '/tokenize', [], 200, ['tokens' => []]);

        $cache->clear();

        $this->assertFalse($cache->has('GET', '/models'));
        $this->assertFalse($cache->has('POST', '/tokenize'));
    }

    public function test_generate_key_consistency(): void
    {
        $cache = new ResponseCache($this->cache);

        $key1 = $cache->generateKey('POST', '/chat', ['a' => 1, 'b' => 2]);
        $key2 = $cache->generateKey('POST', '/chat', ['b' => 2, 'a' => 1]);

        // Keys should be the same regardless of array order
        $this->assertSame($key1, $key2);
    }

    public function test_generate_key_varies_by_method(): void
    {
        $cache = new ResponseCache($this->cache);

        $getKey = $cache->generateKey('GET', '/models', []);
        $postKey = $cache->generateKey('POST', '/models', []);

        $this->assertNotSame($getKey, $postKey);
    }

    public function test_generate_key_varies_by_body(): void
    {
        $cache = new ResponseCache($this->cache);

        $key1 = $cache->generateKey('POST', '/chat', ['message' => 'hello']);
        $key2 = $cache->generateKey('POST', '/chat', ['message' => 'world']);

        $this->assertNotSame($key1, $key2);
    }

    public function test_generate_key_uses_prefix(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            keyPrefix: 'test_prefix_',
        ));

        $key = $cache->generateKey('GET', '/models', []);

        $this->assertStringStartsWith('test_prefix_', $key);
    }

    public function test_get_config(): void
    {
        $config = new CacheConfig(defaultTtl: 7200);
        $cache = new ResponseCache($this->cache, $config);

        $this->assertSame($config, $cache->getConfig());
    }

    public function test_with_config(): void
    {
        $config1 = new CacheConfig(defaultTtl: 3600);
        $config2 = new CacheConfig(defaultTtl: 7200);

        $cache1 = new ResponseCache($this->cache, $config1);
        $cache2 = $cache1->withConfig($config2);

        $this->assertSame($config1, $cache1->getConfig());
        $this->assertSame($config2, $cache2->getConfig());
    }

    public function test_only_caches_get_and_post(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            cacheableEndpoints: ['/models'],
        ));

        $this->assertTrue($cache->shouldCache('GET', '/models'));
        $this->assertTrue($cache->shouldCache('POST', '/models'));
        $this->assertFalse($cache->shouldCache('PUT', '/models'));
        $this->assertFalse($cache->shouldCache('DELETE', '/models'));
        $this->assertFalse($cache->shouldCache('PATCH', '/models'));
    }

    public function test_cached_response_metadata(): void
    {
        $cache = new ResponseCache($this->cache, new CacheConfig(
            cacheableEndpoints: ['/models'],
            defaultTtl: 3600,
        ));

        $cache->set('GET', '/models', [], 200, ['data' => []]);

        $cached = $cache->get('GET', '/models');

        $this->assertNotNull($cached);
        $this->assertSame(3600, $cached->ttl);
        $this->assertLessThanOrEqual(time(), $cached->cachedAt);
        $this->assertFalse($cached->isExpired());
        $this->assertGreaterThan(0, $cached->getRemainingTtl());
    }
}
