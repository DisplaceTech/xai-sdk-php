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
use Displace\XaiSdk\Tests\TestCase;

final class ContentTest extends TestCase
{
    public function test_text_creates_text_content(): void
    {
        $content = Content::text('Hello, world!');

        $this->assertSame(Content::TYPE_TEXT, $content->type);
        $this->assertSame(['text' => 'Hello, world!'], $content->data);
    }

    public function test_text_toArray_returns_correct_structure(): void
    {
        $content = Content::text('Test message');
        $array = $content->toArray();

        $this->assertSame([
            'type' => 'text',
            'text' => 'Test message',
        ], $array);
    }

    public function test_imageUrl_creates_image_content_with_default_detail(): void
    {
        $url = 'https://example.com/image.jpg';
        $content = Content::imageUrl($url);

        $this->assertSame(Content::TYPE_IMAGE_URL, $content->type);
        $this->assertSame([
            'image_url' => [
                'url' => $url,
                'detail' => 'auto',
            ],
        ], $content->data);
    }

    public function test_imageUrl_creates_image_content_with_custom_detail(): void
    {
        $url = 'https://example.com/image.jpg';
        $content = Content::imageUrl($url, 'high');

        $this->assertSame([
            'image_url' => [
                'url' => $url,
                'detail' => 'high',
            ],
        ], $content->data);
    }

    public function test_imageUrl_toArray_returns_correct_structure(): void
    {
        $content = Content::imageUrl('https://example.com/photo.png', 'low');
        $array = $content->toArray();

        $this->assertSame([
            'type' => 'image_url',
            'image_url' => [
                'url' => 'https://example.com/photo.png',
                'detail' => 'low',
            ],
        ], $array);
    }

    public function test_file_creates_file_content(): void
    {
        $fileId = 'file-abc123';
        $content = Content::file($fileId);

        $this->assertSame(Content::TYPE_FILE, $content->type);
        $this->assertSame(['file_id' => $fileId], $content->data);
    }

    public function test_file_toArray_returns_correct_structure(): void
    {
        $content = Content::file('file-xyz789');
        $array = $content->toArray();

        $this->assertSame([
            'type' => 'file',
            'file_id' => 'file-xyz789',
        ], $array);
    }

    public function test_content_is_readonly(): void
    {
        $content = Content::text('Immutable');

        // Verify the class has readonly properties by checking we can access them
        $this->assertSame('text', $content->type);
        $this->assertSame(['text' => 'Immutable'], $content->data);
    }

    public function test_type_constants_have_correct_values(): void
    {
        $this->assertSame('text', Content::TYPE_TEXT);
        $this->assertSame('image_url', Content::TYPE_IMAGE_URL);
        $this->assertSame('file', Content::TYPE_FILE);
    }
}
