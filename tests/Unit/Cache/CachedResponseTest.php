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
use Displace\XaiSdk\Tests\TestCase;

final class CachedResponseTest extends TestCase
{
    public function test_create_cached_response(): void
    {
        $response = new CachedResponse(
            statusCode: 200,
            body: ['data' => 'test'],
            cachedAt: time(),
            ttl: 3600,
        );

        $this->assertSame(200, $response->statusCode);
        $this->assertSame(['data' => 'test'], $response->body);
        $this->assertSame(3600, $response->ttl);
    }

    public function test_from_array(): void
    {
        $data = [
            'status_code' => 200,
            'body' => ['result' => 'ok'],
            'cached_at' => 1234567890,
            'ttl' => 7200,
        ];

        $response = CachedResponse::fromArray($data);

        $this->assertSame(200, $response->statusCode);
        $this->assertSame(['result' => 'ok'], $response->body);
        $this->assertSame(1234567890, $response->cachedAt);
        $this->assertSame(7200, $response->ttl);
    }

    public function test_from_array_with_defaults(): void
    {
        $response = CachedResponse::fromArray([]);

        $this->assertSame(200, $response->statusCode);
        $this->assertSame([], $response->body);
        $this->assertSame(0, $response->ttl);
    }

    public function test_to_array(): void
    {
        $response = new CachedResponse(
            statusCode: 200,
            body: ['data' => 'test'],
            cachedAt: 1234567890,
            ttl: 3600,
        );

        $array = $response->toArray();

        $this->assertSame([
            'status_code' => 200,
            'body' => ['data' => 'test'],
            'cached_at' => 1234567890,
            'ttl' => 3600,
        ], $array);
    }

    public function test_is_expired_false(): void
    {
        $response = new CachedResponse(
            statusCode: 200,
            body: [],
            cachedAt: time(),
            ttl: 3600,
        );

        $this->assertFalse($response->isExpired());
    }

    public function test_is_expired_true(): void
    {
        $response = new CachedResponse(
            statusCode: 200,
            body: [],
            cachedAt: time() - 7200,
            ttl: 3600,
        );

        $this->assertTrue($response->isExpired());
    }

    public function test_get_remaining_ttl(): void
    {
        $response = new CachedResponse(
            statusCode: 200,
            body: [],
            cachedAt: time() - 1800,
            ttl: 3600,
        );

        $remaining = $response->getRemainingTtl();

        // Should be approximately 1800 seconds (with some tolerance for test execution time)
        $this->assertGreaterThan(1790, $remaining);
        $this->assertLessThanOrEqual(1800, $remaining);
    }

    public function test_get_remaining_ttl_when_expired(): void
    {
        $response = new CachedResponse(
            statusCode: 200,
            body: [],
            cachedAt: time() - 7200,
            ttl: 3600,
        );

        $this->assertSame(0, $response->getRemainingTtl());
    }

    public function test_get_age(): void
    {
        $cachedAt = time() - 100;
        $response = new CachedResponse(
            statusCode: 200,
            body: [],
            cachedAt: $cachedAt,
            ttl: 3600,
        );

        $age = $response->getAge();

        // Should be approximately 100 seconds
        $this->assertGreaterThanOrEqual(100, $age);
        $this->assertLessThan(110, $age);
    }

    public function test_round_trip(): void
    {
        $original = new CachedResponse(
            statusCode: 200,
            body: ['nested' => ['data' => 'value']],
            cachedAt: 1234567890,
            ttl: 3600,
        );

        $array = $original->toArray();
        $restored = CachedResponse::fromArray($array);

        $this->assertSame($original->statusCode, $restored->statusCode);
        $this->assertSame($original->body, $restored->body);
        $this->assertSame($original->cachedAt, $restored->cachedAt);
        $this->assertSame($original->ttl, $restored->ttl);
    }
}
