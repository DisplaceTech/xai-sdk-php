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

use Displace\XaiSdk\Chat\Messages\AssistantMessage;

/**
 * A single completion choice from the model.
 */
readonly class Choice
{
    /**
     * Creates a new choice instance.
     *
     * @param int $index The index of this choice.
     * @param AssistantMessage $message The message content.
     * @param string|null $finishReason Why the model stopped generating.
     * @param array<array<string, mixed>> $toolCalls Tool calls made by the model.
     */
    public function __construct(
        public int $index,
        public AssistantMessage $message,
        public ?string $finishReason = null,
        public array $toolCalls = [],
    ) {
    }

    /**
     * Creates a Choice instance from an API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $messageData = $data['message'] ?? [];
        $toolCalls = $messageData['tool_calls'] ?? [];

        return new self(
            index: $data['index'] ?? 0,
            message: new AssistantMessage(
                content: $messageData['content'] ?? '',
                toolCalls: $toolCalls,
                reasoningContent: $messageData['reasoning_content'] ?? null,
            ),
            finishReason: $data['finish_reason'] ?? null,
            toolCalls: $toolCalls,
        );
    }
}
