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

namespace Displace\XaiSdk\Responses;

/**
 * Represents a content block in a message from /v1/responses endpoint.
 *
 * Content blocks can be:
 * - 'output_text': Contains text content
 * - 'refusal': Contains a refusal message
 */
readonly class ContentBlock
{
    /**
     * Creates a new ContentBlock instance.
     *
     * @param string $type The block type ('output_text', 'refusal', etc.).
     * @param string|null $text The text content (for output_text blocks).
     * @param string|null $refusal The refusal message (for refusal blocks).
     * @param array<string, mixed> $rawData The raw block data.
     */
    public function __construct(
        public string $type,
        public ?string $text = null,
        public ?string $refusal = null,
        public array $rawData = [],
    ) {
    }

    /**
     * Creates a ContentBlock from an array.
     *
     * @param array<string, mixed> $data The block data.
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? 'unknown',
            text: $data['text'] ?? null,
            refusal: $data['refusal'] ?? null,
            rawData: $data,
        );
    }

    /**
     * Checks if this is an output text block.
     *
     * @return bool
     */
    public function isOutputText(): bool
    {
        return $this->type === 'output_text';
    }

    /**
     * Checks if this is a refusal block.
     *
     * @return bool
     */
    public function isRefusal(): bool
    {
        return $this->type === 'refusal';
    }
}
