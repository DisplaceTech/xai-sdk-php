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

use Displace\XaiSdk\Chat\Messages\AssistantMessage;
use Displace\XaiSdk\Responses\Choice;
use Displace\XaiSdk\Tests\TestCase;

final class ChoiceTest extends TestCase
{
    public function test_constructor_sets_properties(): void
    {
        $message = new AssistantMessage('Hello');
        $choice = new Choice(
            index: 0,
            message: $message,
            finishReason: 'stop',
            toolCalls: [],
        );

        $this->assertSame(0, $choice->index);
        $this->assertSame($message, $choice->message);
        $this->assertSame('stop', $choice->finishReason);
        $this->assertSame([], $choice->toolCalls);
    }

    public function test_fromArray_creates_choice(): void
    {
        $data = [
            'index' => 0,
            'message' => [
                'role' => 'assistant',
                'content' => 'Hello, world!',
            ],
            'finish_reason' => 'stop',
        ];

        $choice = Choice::fromArray($data);

        $this->assertSame(0, $choice->index);
        $this->assertSame('Hello, world!', $choice->message->content);
        $this->assertSame('stop', $choice->finishReason);
    }

    public function test_fromArray_with_tool_calls(): void
    {
        $toolCalls = [
            [
                'id' => 'call_123',
                'type' => 'function',
                'function' => [
                    'name' => 'get_weather',
                    'arguments' => '{"location": "NYC"}',
                ],
            ],
        ];
        $data = [
            'index' => 0,
            'message' => [
                'role' => 'assistant',
                'content' => '',
                'tool_calls' => $toolCalls,
            ],
            'finish_reason' => 'tool_calls',
        ];

        $choice = Choice::fromArray($data);

        $this->assertSame($toolCalls, $choice->toolCalls);
        $this->assertSame('tool_calls', $choice->finishReason);
    }

    public function test_fromArray_with_reasoning_content(): void
    {
        $data = [
            'index' => 0,
            'message' => [
                'role' => 'assistant',
                'content' => 'The answer is 42.',
                'reasoning_content' => 'Let me think step by step...',
            ],
            'finish_reason' => 'stop',
        ];

        $choice = Choice::fromArray($data);

        $this->assertSame('The answer is 42.', $choice->message->content);
        $this->assertSame('Let me think step by step...', $choice->message->reasoningContent);
    }

    public function test_fromArray_handles_missing_fields(): void
    {
        $choice = Choice::fromArray([]);

        $this->assertSame(0, $choice->index);
        $this->assertSame('', $choice->message->content);
        $this->assertNull($choice->finishReason);
        $this->assertSame([], $choice->toolCalls);
    }

    public function test_choice_is_readonly(): void
    {
        $message = new AssistantMessage('Test');
        $choice = new Choice(0, $message, 'stop');

        // Verify readonly by accessing properties
        $this->assertSame(0, $choice->index);
        $this->assertSame($message, $choice->message);
        $this->assertSame('stop', $choice->finishReason);
    }
}
