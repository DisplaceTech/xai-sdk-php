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

namespace Displace\XaiSdk\Tests\Unit\Http;

use Displace\XaiSdk\Http\HttpConfig;
use Displace\XaiSdk\Tests\TestCase;

final class HttpConfigTest extends TestCase
{
    public function test_default_creates_config_with_default_values(): void
    {
        $config = HttpConfig::default();

        $this->assertEquals(300.0, $config->timeout);
        $this->assertEquals(10.0, $config->connectTimeout);
        $this->assertEquals(0, $config->maxRetries);
        $this->assertEquals(1.0, $config->retryDelay);
        $this->assertEquals(2.0, $config->retryMultiplier);
        $this->assertEquals(30.0, $config->maxRetryDelay);
        $this->assertTrue($config->retryOnConnectionError);
    }

    public function test_with_retries_creates_config_with_retries(): void
    {
        $config = HttpConfig::withRetries(3);

        $this->assertEquals(3, $config->maxRetries);
    }

    public function test_with_timeout_creates_new_config(): void
    {
        $original = HttpConfig::default();
        $modified = $original->withTimeout(60.0);

        $this->assertEquals(300.0, $original->timeout);
        $this->assertEquals(60.0, $modified->timeout);
        $this->assertEquals($original->connectTimeout, $modified->connectTimeout);
    }

    public function test_with_max_retries_creates_new_config(): void
    {
        $original = HttpConfig::default();
        $modified = $original->withMaxRetries(5);

        $this->assertEquals(0, $original->maxRetries);
        $this->assertEquals(5, $modified->maxRetries);
    }

    public function test_has_retries_returns_false_when_zero(): void
    {
        $config = HttpConfig::default();

        $this->assertFalse($config->hasRetries());
    }

    public function test_has_retries_returns_true_when_positive(): void
    {
        $config = HttpConfig::withRetries(1);

        $this->assertTrue($config->hasRetries());
    }

    public function test_should_retry_status_code_returns_true_for_429(): void
    {
        $config = HttpConfig::default();

        $this->assertTrue($config->shouldRetryStatusCode(429));
    }

    public function test_should_retry_status_code_returns_true_for_500(): void
    {
        $config = HttpConfig::default();

        $this->assertTrue($config->shouldRetryStatusCode(500));
    }

    public function test_should_retry_status_code_returns_true_for_502(): void
    {
        $config = HttpConfig::default();

        $this->assertTrue($config->shouldRetryStatusCode(502));
    }

    public function test_should_retry_status_code_returns_true_for_503(): void
    {
        $config = HttpConfig::default();

        $this->assertTrue($config->shouldRetryStatusCode(503));
    }

    public function test_should_retry_status_code_returns_true_for_504(): void
    {
        $config = HttpConfig::default();

        $this->assertTrue($config->shouldRetryStatusCode(504));
    }

    public function test_should_retry_status_code_returns_false_for_400(): void
    {
        $config = HttpConfig::default();

        $this->assertFalse($config->shouldRetryStatusCode(400));
    }

    public function test_should_retry_status_code_returns_false_for_401(): void
    {
        $config = HttpConfig::default();

        $this->assertFalse($config->shouldRetryStatusCode(401));
    }

    public function test_should_retry_status_code_returns_false_for_404(): void
    {
        $config = HttpConfig::default();

        $this->assertFalse($config->shouldRetryStatusCode(404));
    }

    public function test_calculate_retry_delay_returns_base_delay_for_first_attempt(): void
    {
        $config = new HttpConfig(retryDelay: 1.0, retryMultiplier: 2.0);

        $delay = $config->calculateRetryDelay(0);

        // With jitter, delay should be between 0.75 and 1.25
        $this->assertGreaterThanOrEqual(0.75, $delay);
        $this->assertLessThanOrEqual(1.25, $delay);
    }

    public function test_calculate_retry_delay_uses_exponential_backoff(): void
    {
        $config = new HttpConfig(retryDelay: 1.0, retryMultiplier: 2.0, maxRetryDelay: 100.0);

        $delay0 = $config->calculateRetryDelay(0);
        $delay1 = $config->calculateRetryDelay(1);
        $delay2 = $config->calculateRetryDelay(2);

        // Base delays without jitter would be 1, 2, 4
        // With jitter, check they're in reasonable ranges
        $this->assertLessThanOrEqual(1.25, $delay0);
        $this->assertLessThanOrEqual(2.5, $delay1);
        $this->assertLessThanOrEqual(5.0, $delay2);
    }

    public function test_calculate_retry_delay_respects_max_delay(): void
    {
        $config = new HttpConfig(retryDelay: 10.0, retryMultiplier: 10.0, maxRetryDelay: 30.0);

        // After 2 attempts, base delay would be 10 * 10 * 10 = 1000, but capped at 30
        $delay = $config->calculateRetryDelay(2);

        // Should be capped at max + jitter
        $this->assertLessThanOrEqual(37.5, $delay); // 30 + 25% jitter
    }

    public function test_calculate_retry_delay_never_returns_less_than_minimum(): void
    {
        $config = new HttpConfig(retryDelay: 0.01, retryMultiplier: 1.0);

        $delay = $config->calculateRetryDelay(0);

        $this->assertGreaterThanOrEqual(0.1, $delay);
    }

    public function test_custom_retry_status_codes(): void
    {
        $config = new HttpConfig(retryStatusCodes: [502, 503]);

        $this->assertTrue($config->shouldRetryStatusCode(502));
        $this->assertTrue($config->shouldRetryStatusCode(503));
        $this->assertFalse($config->shouldRetryStatusCode(429));
        $this->assertFalse($config->shouldRetryStatusCode(500));
    }
}
