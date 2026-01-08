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

use DateTimeImmutable;

/**
 * A chunk from a streaming response.
 */
readonly class StreamChunk
{
    /**
     * Creates a new stream chunk.
     *
     * @param string $id Unique identifier for the chunk.
     * @param string $model The model used for generation.
     * @param DateTimeImmutable $created When the chunk was created.
     * @param string $content The content delta in this chunk.
     * @param string|null $reasoningContent The reasoning content delta.
     * @param array<array<string, mixed>> $toolCalls Tool call deltas in this chunk.
     * @param string|null $finishReason Why the model stopped (only in final chunk).
     */
    public function __construct(
        public string $id,
        public string $model,
        public DateTimeImmutable $created,
        public string $content = '',
        public ?string $reasoningContent = null,
        public array $toolCalls = [],
        public ?string $finishReason = null,
    ) {
    }

    /**
     * Creates a StreamChunk from an API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $choice = $data['choices'][0] ?? [];
        $delta = $choice['delta'] ?? [];

        return new self(
            id: $data['id'] ?? '',
            model: $data['model'] ?? '',
            created: new DateTimeImmutable()->setTimestamp($data['created'] ?? time()),
            content: $delta['content'] ?? '',
            reasoningContent: $delta['reasoning_content'] ?? null,
            toolCalls: $delta['tool_calls'] ?? [],
            finishReason: $choice['finish_reason'] ?? null,
        );
    }
}
