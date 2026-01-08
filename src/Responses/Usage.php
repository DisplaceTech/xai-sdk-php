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
 * Token usage statistics for a completion.
 */
readonly class Usage
{
    /**
     * Creates a new usage instance.
     *
     * @param int $promptTokens Number of tokens in the prompt.
     * @param int $completionTokens Number of tokens in the completion.
     * @param int $totalTokens Total number of tokens used.
     * @param int $reasoningTokens Number of tokens used for reasoning.
     */
    public function __construct(
        public int $promptTokens = 0,
        public int $completionTokens = 0,
        public int $totalTokens = 0,
        public int $reasoningTokens = 0,
    ) {}

    /**
     * Creates a Usage instance from an API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            promptTokens: $data['prompt_tokens'] ?? 0,
            completionTokens: $data['completion_tokens'] ?? 0,
            totalTokens: $data['total_tokens'] ?? 0,
            reasoningTokens: $data['reasoning_tokens'] ?? 0,
        );
    }
}
