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

use Displace\XaiSdk\Tools\FunctionCall;
use Displace\XaiSdk\Tools\ToolCall;
use PHPUnit\Framework\TestCase;

final class ToolCallTest extends TestCase
{
    public function test_creates_tool_call_with_function(): void
    {
        $functionCall = new FunctionCall(
            name: 'get_weather',
            arguments: '{"city":"New York","units":"C"}',
        );

        $toolCall = new ToolCall(
            id: 'call_123',
            type: 'function',
            function: $functionCall,
        );

        $this->assertSame('call_123', $toolCall->id);
        $this->assertSame('function', $toolCall->type);
        $this->assertSame('get_weather', $toolCall->function->name);
        $this->assertSame('{"city":"New York","units":"C"}', $toolCall->function->arguments);
    }

    public function test_is_client_side_tool_when_no_call_type(): void
    {
        $toolCall = new ToolCall(
            id: 'call_123',
            type: 'function',
            function: new FunctionCall('test', '{}'),
        );

        $this->assertTrue($toolCall->isClientSideTool());
        $this->assertFalse($toolCall->isServerSideTool());
    }

    public function test_is_client_side_tool_when_explicit_client_side(): void
    {
        $toolCall = new ToolCall(
            id: 'call_123',
            type: 'function',
            function: new FunctionCall('test', '{}'),
            callType: ToolCall::TYPE_CLIENT_SIDE,
        );

        $this->assertTrue($toolCall->isClientSideTool());
        $this->assertFalse($toolCall->isServerSideTool());
    }

    public function test_is_server_side_tool_for_web_search(): void
    {
        $toolCall = new ToolCall(
            id: 'call_123',
            type: 'function',
            function: new FunctionCall('web_search', '{}'),
            callType: ToolCall::TYPE_WEB_SEARCH,
        );

        $this->assertFalse($toolCall->isClientSideTool());
        $this->assertTrue($toolCall->isServerSideTool());
    }

    public function test_is_server_side_tool_for_x_search(): void
    {
        $toolCall = new ToolCall(
            id: 'call_123',
            type: 'function',
            function: new FunctionCall('x_search', '{}'),
            callType: ToolCall::TYPE_X_SEARCH,
        );

        $this->assertTrue($toolCall->isServerSideTool());
    }

    public function test_is_server_side_tool_for_code_execution(): void
    {
        $toolCall = new ToolCall(
            id: 'call_123',
            type: 'function',
            function: new FunctionCall('run_code', '{}'),
            callType: ToolCall::TYPE_CODE_EXECUTION,
        );

        $this->assertTrue($toolCall->isServerSideTool());
    }

    public function test_get_tool_call_type_returns_type(): void
    {
        $toolCall = new ToolCall(
            id: 'call_123',
            type: 'function',
            function: new FunctionCall('test', '{}'),
            callType: ToolCall::TYPE_WEB_SEARCH,
        );

        $this->assertSame(ToolCall::TYPE_WEB_SEARCH, $toolCall->getToolCallType());
    }

    public function test_get_tool_call_type_defaults_to_client_side(): void
    {
        $toolCall = new ToolCall(
            id: 'call_123',
            type: 'function',
            function: new FunctionCall('test', '{}'),
        );

        $this->assertSame(ToolCall::TYPE_CLIENT_SIDE, $toolCall->getToolCallType());
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $toolCall = new ToolCall(
            id: 'call_abc',
            type: 'function',
            function: new FunctionCall('search', '{"query":"test"}'),
        );

        $array = $toolCall->toArray();

        $this->assertSame('call_abc', $array['id']);
        $this->assertSame('function', $array['type']);
        $this->assertSame('search', $array['function']['name']);
        $this->assertSame('{"query":"test"}', $array['function']['arguments']);
    }

    public function test_from_array_creates_tool_call(): void
    {
        $data = [
            'id' => 'call_xyz',
            'type' => 'function',
            'function' => [
                'name' => 'get_info',
                'arguments' => '{"id":42}',
            ],
            'call_type' => 'web_search_tool',
        ];

        $toolCall = ToolCall::fromArray($data);

        $this->assertSame('call_xyz', $toolCall->id);
        $this->assertSame('function', $toolCall->type);
        $this->assertSame('get_info', $toolCall->function->name);
        $this->assertSame('{"id":42}', $toolCall->function->arguments);
        $this->assertSame('web_search_tool', $toolCall->callType);
    }

    public function test_from_array_without_call_type(): void
    {
        $data = [
            'id' => 'call_xyz',
            'type' => 'function',
            'function' => [
                'name' => 'test',
                'arguments' => '{}',
            ],
        ];

        $toolCall = ToolCall::fromArray($data);

        $this->assertNull($toolCall->callType);
    }
}
