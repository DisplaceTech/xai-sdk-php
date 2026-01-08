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
 * A tool result message in a chat conversation.
 *
 * Used to provide the result of a tool/function call back to the model.
 */
readonly class ToolMessage implements Message
{
    /**
     * Creates a new tool message.
     *
     * @param string $content The tool result content.
     * @param string|null $toolCallId The ID of the tool call this result is for.
     */
    public function __construct(
        public string $content,
        public ?string $toolCallId = null,
    ) {
    }

    public function getRole(): string
    {
        return 'tool';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'role' => 'tool',
            'content' => $this->content,
        ];

        if ($this->toolCallId !== null) {
            $data['tool_call_id'] = $this->toolCallId;
        }

        return $data;
    }
}
