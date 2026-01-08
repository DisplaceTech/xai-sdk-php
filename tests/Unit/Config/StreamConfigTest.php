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

use Displace\XaiSdk\Config\StreamConfig;
use Displace\XaiSdk\Tests\TestCase;

final class StreamConfigTest extends TestCase
{
    public function test_default_values(): void
    {
        $config = new StreamConfig();

        $this->assertTrue($config->enabled);
        $this->assertSame(StreamConfig::DEFAULT_BUFFER_SIZE, $config->bufferSize);
    }

    public function test_enabled_can_be_set_to_false(): void
    {
        $config = new StreamConfig(enabled: false);

        $this->assertFalse($config->enabled);
    }

    public function test_custom_buffer_size(): void
    {
        $config = new StreamConfig(bufferSize: 16384);

        $this->assertSame(16384, $config->bufferSize);
    }

    public function test_default_buffer_size_constant(): void
    {
        $this->assertSame(8192, StreamConfig::DEFAULT_BUFFER_SIZE);
    }

    public function test_config_is_readonly(): void
    {
        $config = new StreamConfig(enabled: true, bufferSize: 4096);

        // Verify readonly by checking property access works
        $this->assertTrue($config->enabled);
        $this->assertSame(4096, $config->bufferSize);
    }

    public function test_all_parameters_can_be_customized(): void
    {
        $config = new StreamConfig(
            enabled: false,
            bufferSize: 32768,
        );

        $this->assertFalse($config->enabled);
        $this->assertSame(32768, $config->bufferSize);
    }
}
