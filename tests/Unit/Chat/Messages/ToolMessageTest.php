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

namespace Displace\XaiSdk\Tests\Unit\Chat\Messages;

use Displace\XaiSdk\Chat\Messages\Message;
use Displace\XaiSdk\Chat\Messages\ToolMessage;
use Displace\XaiSdk\Tests\TestCase;

final class ToolMessageTest extends TestCase
{
    public function test_implements_message_interface(): void
    {
        $message = new ToolMessage('Result');

        $this->assertInstanceOf(Message::class, $message);
    }

    public function test_get_role_returns_tool(): void
    {
        $message = new ToolMessage('Result');

        $this->assertSame('tool', $message->getRole());
    }

    public function test_content_is_stored(): void
    {
        $message = new ToolMessage('{"temperature": 72}');

        $this->assertSame('{"temperature": 72}', $message->content);
    }

    public function test_tool_call_id_defaults_to_null(): void
    {
        $message = new ToolMessage('Result');

        $this->assertNull($message->toolCallId);
    }

    public function test_tool_call_id_can_be_provided(): void
    {
        $message = new ToolMessage('Result', 'call_abc123');

        $this->assertSame('call_abc123', $message->toolCallId);
    }

    public function test_toArray_without_tool_call_id(): void
    {
        $message = new ToolMessage('Result data');
        $array = $message->toArray();

        $this->assertSame([
            'role' => 'tool',
            'content' => 'Result data',
        ], $array);
    }

    public function test_toArray_with_tool_call_id(): void
    {
        $message = new ToolMessage('Result', 'call_xyz789');
        $array = $message->toArray();

        $this->assertSame([
            'role' => 'tool',
            'content' => 'Result',
            'tool_call_id' => 'call_xyz789',
        ], $array);
    }

    public function test_message_is_readonly(): void
    {
        $message = new ToolMessage('Immutable', 'call_id');

        $this->assertSame('Immutable', $message->content);
        $this->assertSame('call_id', $message->toolCallId);
    }
}
