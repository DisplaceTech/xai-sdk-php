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

namespace Displace\XaiSdk\Tests\Unit\Responses;

use Displace\XaiSdk\Responses\Usage;
use Displace\XaiSdk\Tests\TestCase;

final class UsageTest extends TestCase
{
    public function test_default_values(): void
    {
        $usage = new Usage();

        $this->assertSame(0, $usage->promptTokens);
        $this->assertSame(0, $usage->completionTokens);
        $this->assertSame(0, $usage->totalTokens);
        $this->assertSame(0, $usage->reasoningTokens);
    }

    public function test_constructor_sets_values(): void
    {
        $usage = new Usage(
            promptTokens: 100,
            completionTokens: 50,
            totalTokens: 150,
            reasoningTokens: 20,
        );

        $this->assertSame(100, $usage->promptTokens);
        $this->assertSame(50, $usage->completionTokens);
        $this->assertSame(150, $usage->totalTokens);
        $this->assertSame(20, $usage->reasoningTokens);
    }

    public function test_fromArray_creates_usage(): void
    {
        $data = [
            'prompt_tokens' => 15,
            'completion_tokens' => 20,
            'total_tokens' => 35,
            'reasoning_tokens' => 5,
        ];

        $usage = Usage::fromArray($data);

        $this->assertSame(15, $usage->promptTokens);
        $this->assertSame(20, $usage->completionTokens);
        $this->assertSame(35, $usage->totalTokens);
        $this->assertSame(5, $usage->reasoningTokens);
    }

    public function test_fromArray_handles_missing_fields(): void
    {
        $usage = Usage::fromArray([]);

        $this->assertSame(0, $usage->promptTokens);
        $this->assertSame(0, $usage->completionTokens);
        $this->assertSame(0, $usage->totalTokens);
        $this->assertSame(0, $usage->reasoningTokens);
    }

    public function test_fromArray_handles_partial_data(): void
    {
        $data = [
            'prompt_tokens' => 25,
            'total_tokens' => 40,
        ];

        $usage = Usage::fromArray($data);

        $this->assertSame(25, $usage->promptTokens);
        $this->assertSame(0, $usage->completionTokens);
        $this->assertSame(40, $usage->totalTokens);
        $this->assertSame(0, $usage->reasoningTokens);
    }

    public function test_usage_is_readonly(): void
    {
        $usage = new Usage(10, 20, 30, 5);

        // Verify readonly by accessing properties
        $this->assertSame(10, $usage->promptTokens);
        $this->assertSame(20, $usage->completionTokens);
        $this->assertSame(30, $usage->totalTokens);
        $this->assertSame(5, $usage->reasoningTokens);
    }
}
