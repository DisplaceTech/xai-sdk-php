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

namespace Displace\XaiSdk\Chat\Messages;

/**
 * Represents content within a message.
 *
 * Content can be text, an image URL, or a file reference.
 */
readonly class Content
{
    public const string TYPE_TEXT = 'text';

    public const string TYPE_IMAGE_URL = 'image_url';

    public const string TYPE_FILE = 'file';

    /**
     * Creates a new content object.
     *
     * @param string $type The content type (text, image_url, file).
     * @param array<string, mixed> $data The content data.
     */
    public function __construct(
        public string $type,
        public array $data,
    ) {
    }

    /**
     * Creates text content.
     */
    public static function text(string $text): self
    {
        return new self(self::TYPE_TEXT, ['text' => $text]);
    }

    /**
     * Creates image URL content.
     *
     * @param string $url The image URL or base64-encoded string.
     * @param string $detail The image detail level (auto, low, high).
     */
    public static function imageUrl(string $url, string $detail = 'auto'): self
    {
        return new self(self::TYPE_IMAGE_URL, [
            'image_url' => [
                'url' => $url,
                'detail' => $detail,
            ],
        ]);
    }

    /**
     * Creates file content.
     *
     * @param string $fileId The ID of a previously uploaded file.
     */
    public static function file(string $fileId): self
    {
        return new self(self::TYPE_FILE, ['file_id' => $fileId]);
    }

    /**
     * Converts the content to an array for API requests.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(['type' => $this->type], $this->data);
    }
}
