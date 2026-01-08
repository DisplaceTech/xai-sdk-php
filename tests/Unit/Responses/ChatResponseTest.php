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

use DateTimeImmutable;
use Displace\XaiSdk\Chat\Messages\AssistantMessage;
use Displace\XaiSdk\Responses\ChatResponse;
use Displace\XaiSdk\Responses\Choice;
use Displace\XaiSdk\Responses\Usage;
use Displace\XaiSdk\Tests\TestCase;

final class ChatResponseTest extends TestCase
{
    public function test_constructor_sets_properties(): void
    {
        $created = new DateTimeImmutable();
        $message = new AssistantMessage('Hello');
        $choice = new Choice(0, $message, 'stop');
        $usage = new Usage(10, 20, 30);

        $response = new ChatResponse(
            id: 'chatcmpl-abc123',
            model: 'grok-3',
            created: $created,
            choices: [$choice],
            usage: $usage,
            systemFingerprint: 'fp_123',
        );

        $this->assertSame('chatcmpl-abc123', $response->id);
        $this->assertSame('grok-3', $response->model);
        $this->assertSame($created, $response->created);
        $this->assertCount(1, $response->choices);
        $this->assertSame($usage, $response->usage);
        $this->assertSame('fp_123', $response->systemFingerprint);
    }

    public function test_getContent_returns_first_choice_content(): void
    {
        $message = new AssistantMessage('Hello, world!');
        $choice = new Choice(0, $message, 'stop');
        $response = new ChatResponse(
            id: 'test',
            model: 'grok-3',
            created: new DateTimeImmutable(),
            choices: [$choice],
            usage: new Usage(),
        );

        $this->assertSame('Hello, world!', $response->getContent());
    }

    public function test_getContent_returns_empty_when_no_choices(): void
    {
        $response = new ChatResponse(
            id: 'test',
            model: 'grok-3',
            created: new DateTimeImmutable(),
            choices: [],
            usage: new Usage(),
        );

        $this->assertSame('', $response->getContent());
    }

    public function test_getReasoningContent_returns_reasoning(): void
    {
        $message = new AssistantMessage('Answer', [], 'Reasoning trace');
        $choice = new Choice(0, $message, 'stop');
        $response = new ChatResponse(
            id: 'test',
            model: 'grok-3',
            created: new DateTimeImmutable(),
            choices: [$choice],
            usage: new Usage(),
        );

        $this->assertSame('Reasoning trace', $response->getReasoningContent());
    }

    public function test_getToolCalls_returns_tool_calls(): void
    {
        $toolCalls = [
            ['id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'test']],
        ];
        $message = new AssistantMessage('', $toolCalls);
        $choice = new Choice(0, $message, 'tool_calls', $toolCalls);
        $response = new ChatResponse(
            id: 'test',
            model: 'grok-3',
            created: new DateTimeImmutable(),
            choices: [$choice],
            usage: new Usage(),
        );

        $this->assertSame($toolCalls, $response->getToolCalls());
    }

    public function test_getFinishReason_returns_reason(): void
    {
        $message = new AssistantMessage('Done');
        $choice = new Choice(0, $message, 'stop');
        $response = new ChatResponse(
            id: 'test',
            model: 'grok-3',
            created: new DateTimeImmutable(),
            choices: [$choice],
            usage: new Usage(),
        );

        $this->assertSame('stop', $response->getFinishReason());
    }

    public function test_toMessage_returns_assistant_message(): void
    {
        $message = new AssistantMessage('Hello');
        $choice = new Choice(0, $message, 'stop');
        $response = new ChatResponse(
            id: 'test',
            model: 'grok-3',
            created: new DateTimeImmutable(),
            choices: [$choice],
            usage: new Usage(),
        );

        $result = $response->toMessage();

        $this->assertInstanceOf(AssistantMessage::class, $result);
        $this->assertSame('Hello', $result->content);
    }

    public function test_toMessage_returns_empty_when_no_choices(): void
    {
        $response = new ChatResponse(
            id: 'test',
            model: 'grok-3',
            created: new DateTimeImmutable(),
            choices: [],
            usage: new Usage(),
        );

        $result = $response->toMessage();

        $this->assertInstanceOf(AssistantMessage::class, $result);
        $this->assertSame('', $result->content);
    }

    public function test_fromArray_creates_response(): void
    {
        $data = $this->loadJsonFixture('chat_completion_response.json');

        $response = ChatResponse::fromArray($data);

        $this->assertSame('chatcmpl-abc123', $response->id);
        $this->assertSame('grok-3', $response->model);
        $this->assertCount(1, $response->choices);
        $this->assertStringContainsString('Grok', $response->getContent());
        $this->assertSame(15, $response->usage->promptTokens);
        $this->assertSame(20, $response->usage->completionTokens);
        $this->assertSame(35, $response->usage->totalTokens);
    }

    public function test_fromArray_with_tool_calls(): void
    {
        $data = $this->loadJsonFixture('chat_completion_with_tool_call.json');

        $response = ChatResponse::fromArray($data);

        $this->assertSame('tool_calls', $response->getFinishReason());
        $toolCalls = $response->getToolCalls();
        $this->assertCount(1, $toolCalls);
        $this->assertSame('get_weather', $toolCalls[0]['function']['name']);
    }

    public function test_fromArray_handles_missing_fields(): void
    {
        $response = ChatResponse::fromArray([]);

        $this->assertSame('', $response->id);
        $this->assertSame('', $response->model);
        $this->assertEmpty($response->choices);
        $this->assertNull($response->systemFingerprint);
    }
}
