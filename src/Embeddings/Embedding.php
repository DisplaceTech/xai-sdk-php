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
 * Represents a single embedding result from the embeddings API.
 *
 * Each embedding contains a vector of floating-point numbers representing
 * the semantic meaning of the input text in a high-dimensional space.
 */
readonly class Embedding
{
    /**
     * Creates a new Embedding instance.
     *
     * @param int $index The index of this embedding in the response (corresponds to input order).
     * @param array<float> $embedding The embedding vector.
     * @param string $object The object type (always "embedding").
     */
    public function __construct(
        public int $index,
        public array $embedding,
        public string $object = 'embedding',
    ) {
    }

    /**
     * Creates an Embedding instance from an API response array.
     *
     * @param array<string, mixed> $data The raw API response data.
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            index: $data['index'] ?? 0,
            embedding: $data['embedding'] ?? [],
            object: $data['object'] ?? 'embedding',
        );
    }

    /**
     * Gets the dimensionality of the embedding vector.
     *
     * @return int The number of dimensions in the embedding.
     */
    public function getDimensions(): int
    {
        return count($this->embedding);
    }
}
