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

use Displace\XaiSdk\Cache\CacheConfig;
use Displace\XaiSdk\Tests\TestCase;

final class CacheConfigTest extends TestCase
{
    public function test_default_config(): void
    {
        $config = new CacheConfig();

        $this->assertTrue($config->enabled);
        $this->assertSame(3600, $config->defaultTtl);
        $this->assertFalse($config->cacheStreaming);
        $this->assertFalse($config->requireSeed);
        $this->assertSame('xai_sdk_', $config->keyPrefix);
    }

    public function test_disabled_config(): void
    {
        $config = CacheConfig::disabled();

        $this->assertFalse($config->enabled);
    }

    public function test_deterministic_only(): void
    {
        $config = CacheConfig::deterministicOnly(7200);

        $this->assertTrue($config->enabled);
        $this->assertTrue($config->requireSeed);
        $this->assertSame(7200, $config->defaultTtl);
        $this->assertContains('/chat/completions', $config->cacheableEndpoints);
    }

    public function test_metadata_only(): void
    {
        $config = CacheConfig::metadataOnly();

        $this->assertTrue($config->enabled);
        $this->assertContains('/models', $config->cacheableEndpoints);
        $this->assertContains('/tokenize', $config->cacheableEndpoints);
    }

    public function test_is_cacheable_endpoint(): void
    {
        $config = new CacheConfig(
            cacheableEndpoints: ['/models', '/chat/completions'],
        );

        $this->assertTrue($config->isCacheableEndpoint('/models'));
        $this->assertTrue($config->isCacheableEndpoint('/models/grok-3'));
        $this->assertTrue($config->isCacheableEndpoint('/chat/completions'));
        $this->assertFalse($config->isCacheableEndpoint('/unknown'));
    }

    public function test_disabled_config_not_cacheable(): void
    {
        $config = CacheConfig::disabled();

        $this->assertFalse($config->isCacheableEndpoint('/models'));
    }

    public function test_get_ttl_for_endpoint(): void
    {
        $config = new CacheConfig(
            defaultTtl: 3600,
            endpointTtls: [
                '/models' => 86400,
                '/tokenize' => 7200,
            ],
        );

        $this->assertSame(86400, $config->getTtlForEndpoint('/models'));
        $this->assertSame(86400, $config->getTtlForEndpoint('/models/grok-3'));
        $this->assertSame(7200, $config->getTtlForEndpoint('/tokenize'));
        $this->assertSame(3600, $config->getTtlForEndpoint('/chat/completions'));
    }

    public function test_with_ttl(): void
    {
        $config = new CacheConfig(defaultTtl: 3600);
        $newConfig = $config->withTtl(7200);

        $this->assertSame(3600, $config->defaultTtl);
        $this->assertSame(7200, $newConfig->defaultTtl);
    }

    public function test_with_endpoints(): void
    {
        $config = new CacheConfig(
            cacheableEndpoints: ['/models'],
        );

        $newConfig = $config->withEndpoints(['/chat/completions']);

        $this->assertCount(1, $config->cacheableEndpoints);
        $this->assertCount(2, $newConfig->cacheableEndpoints);
        $this->assertContains('/models', $newConfig->cacheableEndpoints);
        $this->assertContains('/chat/completions', $newConfig->cacheableEndpoints);
    }

    public function test_custom_key_prefix(): void
    {
        $config = new CacheConfig(
            keyPrefix: 'my_app_',
        );

        $this->assertSame('my_app_', $config->keyPrefix);
    }

    public function test_cache_streaming_option(): void
    {
        $config = new CacheConfig(cacheStreaming: true);

        $this->assertTrue($config->cacheStreaming);
    }

    public function test_require_seed_option(): void
    {
        $config = new CacheConfig(requireSeed: true);

        $this->assertTrue($config->requireSeed);
    }

    public function test_immutability(): void
    {
        $original = new CacheConfig(defaultTtl: 3600);
        $modified = $original->withTtl(7200);

        $this->assertNotSame($original, $modified);
        $this->assertSame(3600, $original->defaultTtl);
        $this->assertSame(7200, $modified->defaultTtl);
    }
}
