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

use Displace\XaiSdk\Chat\Messages\AssistantMessage;
use Displace\XaiSdk\Chat\Messages\Message;
use Displace\XaiSdk\Tests\TestCase;

final class AssistantMessageTest extends TestCase
{
    public function test_implements_message_interface(): void
    {
        $message = new AssistantMessage('Hello, I am the assistant.');

        $this->assertInstanceOf(Message::class, $message);
    }

    public function test_get_role_returns_assistant(): void
    {
        $message = new AssistantMessage('Hello');

        $this->assertSame('assistant', $message->getRole());
    }

    public function test_content_is_stored(): void
    {
        $message = new AssistantMessage('I can help you with that.');

        $this->assertSame('I can help you with that.', $message->content);
    }

    public function test_tool_calls_default_to_empty_array(): void
    {
        $message = new AssistantMessage('No tools needed.');

        $this->assertSame([], $message->toolCalls);
    }

    public function test_tool_calls_can_be_provided(): void
    {
        $toolCalls = [
            [
                'id' => 'call_123',
                'type' => 'function',
                'function' => [
                    'name' => 'get_weather',
                    'arguments' => '{"location": "New York"}',
                ],
            ],
        ];
        $message = new AssistantMessage('', $toolCalls);

        $this->assertSame($toolCalls, $message->toolCalls);
    }

    public function test_reasoning_content_defaults_to_null(): void
    {
        $message = new AssistantMessage('Simple response');

        $this->assertNull($message->reasoningContent);
    }

    public function test_reasoning_content_can_be_provided(): void
    {
        $message = new AssistantMessage(
            'The answer is 42.',
            [],
            'Let me think about this step by step...'
        );

        $this->assertSame('Let me think about this step by step...', $message->reasoningContent);
    }

    public function test_toArray_with_simple_content(): void
    {
        $message = new AssistantMessage('Hello, world!');
        $array = $message->toArray();

        $this->assertSame([
            'role' => 'assistant',
            'content' => 'Hello, world!',
        ], $array);
    }

    public function test_toArray_includes_tool_calls_when_present(): void
    {
        $toolCalls = [
            [
                'id' => 'call_456',
                'type' => 'function',
                'function' => ['name' => 'test', 'arguments' => '{}'],
            ],
        ];
        $message = new AssistantMessage('', $toolCalls);
        $array = $message->toArray();

        $this->assertArrayHasKey('tool_calls', $array);
        $this->assertSame($toolCalls, $array['tool_calls']);
    }

    public function test_toArray_excludes_tool_calls_when_empty(): void
    {
        $message = new AssistantMessage('No tools');
        $array = $message->toArray();

        $this->assertArrayNotHasKey('tool_calls', $array);
    }

    public function test_toArray_includes_reasoning_content_when_present(): void
    {
        $message = new AssistantMessage('Answer', [], 'Reasoning trace');
        $array = $message->toArray();

        $this->assertArrayHasKey('reasoning_content', $array);
        $this->assertSame('Reasoning trace', $array['reasoning_content']);
    }

    public function test_toArray_excludes_reasoning_content_when_null(): void
    {
        $message = new AssistantMessage('Simple answer');
        $array = $message->toArray();

        $this->assertArrayNotHasKey('reasoning_content', $array);
    }

    public function test_message_is_readonly(): void
    {
        $message = new AssistantMessage('Immutable', [], 'Reasoning');

        $this->assertSame('Immutable', $message->content);
        $this->assertSame([], $message->toolCalls);
        $this->assertSame('Reasoning', $message->reasoningContent);
    }
}
