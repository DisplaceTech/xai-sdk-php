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
 * An assistant (model) message in a chat conversation.
 */
readonly class AssistantMessage implements Message
{
    /**
     * Creates a new assistant message.
     *
     * @param string $content The message content.
     * @param array<array<string, mixed>> $toolCalls Tool calls made by the assistant.
     * @param string|null $reasoningContent The reasoning trace from the model.
     */
    public function __construct(
        public string $content,
        public array $toolCalls = [],
        public ?string $reasoningContent = null,
    ) {}

    public function getRole(): string
    {
        return 'assistant';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'role' => 'assistant',
            'content' => $this->content,
        ];

        if ($this->toolCalls !== []) {
            $data['tool_calls'] = $this->toolCalls;
        }

        if ($this->reasoningContent !== null) {
            $data['reasoning_content'] = $this->reasoningContent;
        }

        return $data;
    }
}
