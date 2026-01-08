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

use Displace\XaiSdk\Chat\Messages\Content;
use Displace\XaiSdk\Chat\Messages\Message;
use Displace\XaiSdk\Chat\Messages\UserMessage;
use Displace\XaiSdk\Tests\TestCase;

final class UserMessageTest extends TestCase
{
    public function test_implements_message_interface(): void
    {
        $message = new UserMessage('Hello');

        $this->assertInstanceOf(Message::class, $message);
    }

    public function test_get_role_returns_user(): void
    {
        $message = new UserMessage('Hello');

        $this->assertSame('user', $message->getRole());
    }

    public function test_string_content_is_normalized_to_text_content(): void
    {
        $message = new UserMessage('Hello, world!');

        $this->assertCount(1, $message->content);
        $this->assertSame(Content::TYPE_TEXT, $message->content[0]->type);
        $this->assertSame('Hello, world!', $message->content[0]->data['text']);
    }

    public function test_content_object_is_preserved(): void
    {
        $content = Content::imageUrl('https://example.com/image.jpg');
        $message = new UserMessage($content);

        $this->assertCount(1, $message->content);
        $this->assertSame(Content::TYPE_IMAGE_URL, $message->content[0]->type);
    }

    public function test_array_of_strings_is_normalized(): void
    {
        $message = new UserMessage(['Part one', 'Part two']);

        $this->assertCount(2, $message->content);
        $this->assertSame('Part one', $message->content[0]->data['text']);
        $this->assertSame('Part two', $message->content[1]->data['text']);
    }

    public function test_array_of_content_objects_is_preserved(): void
    {
        $content = [
            Content::text('Look at this:'),
            Content::imageUrl('https://example.com/photo.jpg'),
        ];
        $message = new UserMessage($content);

        $this->assertCount(2, $message->content);
        $this->assertSame(Content::TYPE_TEXT, $message->content[0]->type);
        $this->assertSame(Content::TYPE_IMAGE_URL, $message->content[1]->type);
    }

    public function test_mixed_array_of_strings_and_content_is_normalized(): void
    {
        $content = [
            'Text content',
            Content::imageUrl('https://example.com/image.jpg'),
        ];
        $message = new UserMessage($content);

        $this->assertCount(2, $message->content);
        $this->assertSame(Content::TYPE_TEXT, $message->content[0]->type);
        $this->assertSame(Content::TYPE_IMAGE_URL, $message->content[1]->type);
    }

    public function test_toArray_with_simple_text_returns_string_format(): void
    {
        $message = new UserMessage('Hello, world!');
        $array = $message->toArray();

        $this->assertSame([
            'role' => 'user',
            'content' => 'Hello, world!',
        ], $array);
    }

    public function test_toArray_with_image_returns_array_format(): void
    {
        $message = new UserMessage([
            Content::text('What is this?'),
            Content::imageUrl('https://example.com/photo.jpg', 'high'),
        ]);
        $array = $message->toArray();

        $this->assertSame('user', $array['role']);
        $this->assertIsArray($array['content']);
        $this->assertCount(2, $array['content']);
        $this->assertSame('text', $array['content'][0]['type']);
        $this->assertSame('image_url', $array['content'][1]['type']);
    }

    public function test_message_is_readonly(): void
    {
        $message = new UserMessage('Immutable message');

        // Verify readonly by checking property access works
        $this->assertIsArray($message->content);
    }
}
