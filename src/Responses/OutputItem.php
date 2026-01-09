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
 * Represents an item in the output array from /v1/responses endpoint.
 *
 * Output items can be:
 * - 'message': Contains content blocks with text
 * - 'custom_tool_call': Represents a tool call execution
 */
readonly class OutputItem
{
    /**
     * Creates a new OutputItem instance.
     *
     * @param string $type The item type ('message', 'custom_tool_call', etc.).
     * @param string|null $role The role (for messages).
     * @param array<ContentBlock> $content Content blocks (for messages).
     * @param string|null $callId The call ID (for tool calls).
     * @param string|null $name The tool name (for tool calls).
     * @param string|null $toolStatus The tool call status.
     * @param array<string, mixed> $rawData The raw item data.
     */
    public function __construct(
        public string $type,
        public ?string $role = null,
        public array $content = [],
        public ?string $callId = null,
        public ?string $name = null,
        public ?string $toolStatus = null,
        public array $rawData = [],
    ) {
    }

    /**
     * Creates an OutputItem from an array.
     *
     * @param array<string, mixed> $data The item data.
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $content = [];

        if (isset($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $block) {
                $content[] = ContentBlock::fromArray($block);
            }
        }

        return new self(
            type: $data['type'] ?? 'unknown',
            role: $data['role'] ?? null,
            content: $content,
            callId: $data['call_id'] ?? null,
            name: $data['name'] ?? null,
            toolStatus: $data['status'] ?? null,
            rawData: $data,
        );
    }

    /**
     * Gets the text content from this item.
     *
     * Only applicable for 'message' type items.
     *
     * @return string The combined text from output_text blocks.
     */
    public function getText(): string
    {
        $text = '';

        foreach ($this->content as $block) {
            if ($block->type === 'output_text') {
                $text .= $block->text;
            }
        }

        return $text;
    }

    /**
     * Checks if this is a message item.
     *
     * @return bool
     */
    public function isMessage(): bool
    {
        return $this->type === 'message';
    }

    /**
     * Checks if this is a tool call item.
     *
     * @return bool
     */
    public function isToolCall(): bool
    {
        return $this->type === 'custom_tool_call';
    }
}
