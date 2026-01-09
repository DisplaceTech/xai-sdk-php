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

namespace Displace\XaiSdk\Embeddings;

/**
 * Token usage statistics for an embeddings request.
 *
 * Unlike chat completions, embeddings only have prompt tokens
 * since there is no generated output text.
 */
readonly class EmbeddingsUsage
{
    /**
     * Creates a new usage instance.
     *
     * @param int $promptTokens Number of tokens in the input.
     * @param int $totalTokens Total number of tokens used.
     */
    public function __construct(
        public int $promptTokens = 0,
        public int $totalTokens = 0,
    ) {
    }

    /**
     * Creates an EmbeddingsUsage instance from an API response array.
     *
     * @param array<string, mixed> $data The raw API response data.
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            promptTokens: $data['prompt_tokens'] ?? 0,
            totalTokens: $data['total_tokens'] ?? 0,
        );
    }
}
