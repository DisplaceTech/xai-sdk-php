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
use Displace\XaiSdk\Chat\Messages\SystemMessage;
use Displace\XaiSdk\Tests\TestCase;

final class SystemMessageTest extends TestCase
{
    public function test_implements_message_interface(): void
    {
        $message = new SystemMessage('You are a helpful assistant.');

        $this->assertInstanceOf(Message::class, $message);
    }

    public function test_get_role_returns_system(): void
    {
        $message = new SystemMessage('You are a helpful assistant.');

        $this->assertSame('system', $message->getRole());
    }

    public function test_string_content_is_normalized(): void
    {
        $message = new SystemMessage('You are a pirate.');

        $this->assertCount(1, $message->content);
        $this->assertSame(Content::TYPE_TEXT, $message->content[0]->type);
        $this->assertSame('You are a pirate.', $message->content[0]->data['text']);
    }

    public function test_content_object_is_preserved(): void
    {
        $content = Content::text('Custom instructions');
        $message = new SystemMessage($content);

        $this->assertCount(1, $message->content);
        $this->assertSame('Custom instructions', $message->content[0]->data['text']);
    }

    public function test_array_of_content_is_preserved(): void
    {
        $content = [
            Content::text('Part one'),
            Content::text('Part two'),
        ];
        $message = new SystemMessage($content);

        $this->assertCount(2, $message->content);
    }

    public function test_toArray_with_simple_text_returns_string_format(): void
    {
        $message = new SystemMessage('You are a helpful assistant.');
        $array = $message->toArray();

        $this->assertSame([
            'role' => 'system',
            'content' => 'You are a helpful assistant.',
        ], $array);
    }

    public function test_toArray_with_multiple_content_returns_array_format(): void
    {
        $message = new SystemMessage([
            Content::text('Be helpful.'),
            Content::text('Be concise.'),
        ]);
        $array = $message->toArray();

        $this->assertSame('system', $array['role']);
        $this->assertIsArray($array['content']);
        $this->assertCount(2, $array['content']);
    }

    public function test_message_is_readonly(): void
    {
        $message = new SystemMessage('Immutable');

        $this->assertIsArray($message->content);
    }
}
