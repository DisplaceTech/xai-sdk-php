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

namespace Displace\XaiSdk\Tests\Unit\Embeddings;

use Displace\XaiSdk\Embeddings\EmbeddingsUsage;
use Displace\XaiSdk\Tests\TestCase;

final class EmbeddingsUsageTest extends TestCase
{
    public function test_default_values(): void
    {
        $usage = new EmbeddingsUsage();

        $this->assertSame(0, $usage->promptTokens);
        $this->assertSame(0, $usage->totalTokens);
    }

    public function test_constructor_sets_values(): void
    {
        $usage = new EmbeddingsUsage(
            promptTokens: 100,
            totalTokens: 100,
        );

        $this->assertSame(100, $usage->promptTokens);
        $this->assertSame(100, $usage->totalTokens);
    }

    public function test_fromArray_creates_usage(): void
    {
        $data = [
            'prompt_tokens' => 50,
            'total_tokens' => 50,
        ];

        $usage = EmbeddingsUsage::fromArray($data);

        $this->assertSame(50, $usage->promptTokens);
        $this->assertSame(50, $usage->totalTokens);
    }

    public function test_fromArray_handles_missing_fields(): void
    {
        $usage = EmbeddingsUsage::fromArray([]);

        $this->assertSame(0, $usage->promptTokens);
        $this->assertSame(0, $usage->totalTokens);
    }

    public function test_fromArray_handles_partial_data(): void
    {
        $data = [
            'prompt_tokens' => 25,
        ];

        $usage = EmbeddingsUsage::fromArray($data);

        $this->assertSame(25, $usage->promptTokens);
        $this->assertSame(0, $usage->totalTokens);
    }

    public function test_usage_is_readonly(): void
    {
        $usage = new EmbeddingsUsage(10, 10);

        // Verify readonly by accessing properties
        $this->assertSame(10, $usage->promptTokens);
        $this->assertSame(10, $usage->totalTokens);
    }
}
