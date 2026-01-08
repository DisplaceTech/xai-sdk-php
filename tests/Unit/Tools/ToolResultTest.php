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

namespace Displace\XaiSdk\Tests\Unit\Tools;

use Displace\XaiSdk\Tools\ToolResult;
use PHPUnit\Framework\TestCase;

final class ToolResultTest extends TestCase
{
    public function test_creates_tool_result_with_content(): void
    {
        $result = new ToolResult(content: 'The weather is sunny');

        $this->assertSame('The weather is sunny', $result->content);
        $this->assertNull($result->toolCallId);
    }

    public function test_creates_tool_result_with_tool_call_id(): void
    {
        $result = new ToolResult(
            content: 'Result data',
            toolCallId: 'call_123',
        );

        $this->assertSame('Result data', $result->content);
        $this->assertSame('call_123', $result->toolCallId);
    }

    public function test_get_role_returns_tool(): void
    {
        $result = new ToolResult(content: 'test');

        $this->assertSame('tool', $result->getRole());
        $this->assertSame(ToolResult::ROLE, $result->getRole());
    }

    public function test_to_array_without_tool_call_id(): void
    {
        $result = new ToolResult(content: 'Weather data');

        $array = $result->toArray();

        $this->assertSame('tool', $array['role']);
        $this->assertSame('Weather data', $array['content']);
        $this->assertArrayNotHasKey('tool_call_id', $array);
    }

    public function test_to_array_with_tool_call_id(): void
    {
        $result = new ToolResult(
            content: 'Search results',
            toolCallId: 'call_abc',
        );

        $array = $result->toArray();

        $this->assertSame('tool', $array['role']);
        $this->assertSame('Search results', $array['content']);
        $this->assertSame('call_abc', $array['tool_call_id']);
    }

    public function test_from_array_without_tool_call_id(): void
    {
        $data = ['content' => 'Result text'];

        $result = ToolResult::fromArray($data);

        $this->assertSame('Result text', $result->content);
        $this->assertNull($result->toolCallId);
    }

    public function test_from_array_with_tool_call_id(): void
    {
        $data = [
            'content' => 'API response',
            'tool_call_id' => 'call_xyz',
        ];

        $result = ToolResult::fromArray($data);

        $this->assertSame('API response', $result->content);
        $this->assertSame('call_xyz', $result->toolCallId);
    }
}
