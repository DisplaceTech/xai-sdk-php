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

namespace Displace\XaiSdk\Tests\Unit\Chat;

use Displace\XaiSdk\Chat\Messages\AssistantMessage;
use Displace\XaiSdk\Chat\Messages\Content;
use Displace\XaiSdk\Chat\Messages\SystemMessage;
use Displace\XaiSdk\Chat\Messages\ToolMessage;
use Displace\XaiSdk\Chat\Messages\UserMessage;
use Displace\XaiSdk\Tests\TestCase;

use function Displace\XaiSdk\Chat\assistant;
use function Displace\XaiSdk\Chat\file;
use function Displace\XaiSdk\Chat\image;
use function Displace\XaiSdk\Chat\system;
use function Displace\XaiSdk\Chat\text;
use function Displace\XaiSdk\Chat\toolResult;
use function Displace\XaiSdk\Chat\user;

final class FunctionsTest extends TestCase
{
    public function test_system_creates_system_message(): void
    {
        $message = system('You are helpful.');

        $this->assertInstanceOf(SystemMessage::class, $message);
        $this->assertSame('system', $message->getRole());
    }

    public function test_system_with_multiple_content(): void
    {
        $message = system('Be helpful.', 'Be concise.');

        $this->assertCount(2, $message->content);
    }

    public function test_system_with_content_objects(): void
    {
        $message = system(Content::text('Custom text'));

        $this->assertCount(1, $message->content);
        $this->assertSame('Custom text', $message->content[0]->data['text']);
    }

    public function test_user_creates_user_message(): void
    {
        $message = user('Hello!');

        $this->assertInstanceOf(UserMessage::class, $message);
        $this->assertSame('user', $message->getRole());
    }

    public function test_user_with_multiple_content(): void
    {
        $message = user('Look at this:', 'Second part');

        $this->assertCount(2, $message->content);
    }

    public function test_user_with_image(): void
    {
        $message = user('What is this?', image('https://example.com/photo.jpg'));

        $this->assertCount(2, $message->content);
        $this->assertSame(Content::TYPE_TEXT, $message->content[0]->type);
        $this->assertSame(Content::TYPE_IMAGE_URL, $message->content[1]->type);
    }

    public function test_assistant_creates_assistant_message(): void
    {
        $message = assistant('I can help with that.');

        $this->assertInstanceOf(AssistantMessage::class, $message);
        $this->assertSame('assistant', $message->getRole());
        $this->assertSame('I can help with that.', $message->content);
    }

    public function test_toolResult_creates_tool_message(): void
    {
        $message = toolResult('{"result": "success"}');

        $this->assertInstanceOf(ToolMessage::class, $message);
        $this->assertSame('tool', $message->getRole());
    }

    public function test_toolResult_with_tool_call_id(): void
    {
        $message = toolResult('Result', 'call_abc');

        $this->assertSame('call_abc', $message->toolCallId);
    }

    public function test_image_creates_image_content(): void
    {
        $content = image('https://example.com/photo.jpg');

        $this->assertInstanceOf(Content::class, $content);
        $this->assertSame(Content::TYPE_IMAGE_URL, $content->type);
        $this->assertSame('auto', $content->data['image_url']['detail']);
    }

    public function test_image_with_custom_detail(): void
    {
        $content = image('https://example.com/photo.jpg', 'high');

        $this->assertSame('high', $content->data['image_url']['detail']);
    }

    public function test_text_creates_text_content(): void
    {
        $content = text('Plain text');

        $this->assertInstanceOf(Content::class, $content);
        $this->assertSame(Content::TYPE_TEXT, $content->type);
        $this->assertSame('Plain text', $content->data['text']);
    }

    public function test_file_creates_file_content(): void
    {
        $content = file('file-abc123');

        $this->assertInstanceOf(Content::class, $content);
        $this->assertSame(Content::TYPE_FILE, $content->type);
        $this->assertSame('file-abc123', $content->data['file_id']);
    }

    public function test_complex_conversation_setup(): void
    {
        $messages = [
            system('You are a weather assistant.'),
            user('What is the weather in NYC?'),
            assistant('I will check the weather for you.'),
            toolResult('{"temp": 72, "conditions": "sunny"}', 'call_weather_1'),
        ];

        $this->assertCount(4, $messages);
        $this->assertSame('system', $messages[0]->getRole());
        $this->assertSame('user', $messages[1]->getRole());
        $this->assertSame('assistant', $messages[2]->getRole());
        $this->assertSame('tool', $messages[3]->getRole());
    }

    public function test_user_with_file_and_text(): void
    {
        $message = user('Summarize this document:', file('file-doc123'));

        $this->assertCount(2, $message->content);
        $this->assertSame(Content::TYPE_TEXT, $message->content[0]->type);
        $this->assertSame(Content::TYPE_FILE, $message->content[1]->type);
    }
}
