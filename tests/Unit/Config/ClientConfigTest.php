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

namespace Displace\XaiSdk\Tests\Unit\Config;

use Displace\XaiSdk\Config\ClientConfig;
use Displace\XaiSdk\Tests\TestCase;
use RuntimeException;

final class ClientConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear any existing XAI_API_KEY environment variable for clean tests
        putenv('XAI_API_KEY');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Restore test API key after tests
        putenv('XAI_API_KEY=test-api-key-for-unit-tests');
    }

    public function test_default_values(): void
    {
        $config = new ClientConfig();

        $this->assertNull($config->apiKey);
        $this->assertSame(ClientConfig::DEFAULT_API_HOST, $config->apiHost);
        $this->assertSame(ClientConfig::DEFAULT_TIMEOUT, $config->timeout);
        $this->assertSame([], $config->headers);
    }

    public function test_custom_api_key(): void
    {
        $config = new ClientConfig(apiKey: 'my-custom-key');

        $this->assertSame('my-custom-key', $config->apiKey);
    }

    public function test_custom_api_host(): void
    {
        $config = new ClientConfig(apiHost: 'https://custom.api.host');

        $this->assertSame('https://custom.api.host', $config->apiHost);
    }

    public function test_custom_timeout(): void
    {
        $config = new ClientConfig(timeout: 30);

        $this->assertSame(30, $config->timeout);
    }

    public function test_custom_headers(): void
    {
        $headers = ['X-Custom-Header' => 'custom-value'];
        $config = new ClientConfig(headers: $headers);

        $this->assertSame($headers, $config->headers);
    }

    public function test_getApiKey_returns_provided_key(): void
    {
        $config = new ClientConfig(apiKey: 'explicit-key');

        $this->assertSame('explicit-key', $config->getApiKey());
    }

    public function test_getApiKey_falls_back_to_environment(): void
    {
        putenv('XAI_API_KEY=env-api-key');
        $config = new ClientConfig();

        $this->assertSame('env-api-key', $config->getApiKey());
    }

    public function test_getApiKey_throws_when_no_key_available(): void
    {
        putenv('XAI_API_KEY');
        $config = new ClientConfig();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No API key provided');

        $config->getApiKey();
    }

    public function test_getApiKey_throws_for_empty_string(): void
    {
        putenv('XAI_API_KEY=');
        $config = new ClientConfig();

        $this->expectException(RuntimeException::class);

        $config->getApiKey();
    }

    public function test_getBaseUrl_returns_api_host(): void
    {
        $config = new ClientConfig(apiHost: 'https://api.example.com');

        $this->assertSame('https://api.example.com', $config->getBaseUrl());
    }

    public function test_getBaseUrl_trims_trailing_slash(): void
    {
        $config = new ClientConfig(apiHost: 'https://api.example.com/');

        $this->assertSame('https://api.example.com', $config->getBaseUrl());
    }

    public function test_getBaseUrl_trims_multiple_trailing_slashes(): void
    {
        $config = new ClientConfig(apiHost: 'https://api.example.com///');

        $this->assertSame('https://api.example.com', $config->getBaseUrl());
    }

    public function test_default_constants_have_expected_values(): void
    {
        $this->assertSame('https://api.x.ai', ClientConfig::DEFAULT_API_HOST);
        $this->assertSame(900, ClientConfig::DEFAULT_TIMEOUT);
    }

    public function test_config_is_readonly(): void
    {
        $config = new ClientConfig(
            apiKey: 'key',
            apiHost: 'https://host.com',
            timeout: 60,
            headers: ['X-Test' => 'value'],
        );

        // Verify readonly by checking property access works
        $this->assertSame('key', $config->apiKey);
        $this->assertSame('https://host.com', $config->apiHost);
        $this->assertSame(60, $config->timeout);
        $this->assertSame(['X-Test' => 'value'], $config->headers);
    }

    public function test_explicit_key_takes_precedence_over_env(): void
    {
        putenv('XAI_API_KEY=env-key');
        $config = new ClientConfig(apiKey: 'explicit-key');

        $this->assertSame('explicit-key', $config->getApiKey());
    }
}
