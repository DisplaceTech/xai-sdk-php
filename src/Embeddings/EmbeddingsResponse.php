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
 * Response from the embeddings API.
 *
 * Contains the embedding vectors for the input text(s), along with
 * usage information and metadata.
 */
readonly class EmbeddingsResponse
{
    /**
     * Creates a new EmbeddingsResponse instance.
     *
     * @param string $id Unique identifier for the response.
     * @param string $model The model used to generate the embeddings.
     * @param array<Embedding> $data The embedding results.
     * @param EmbeddingsUsage $usage Token usage statistics.
     * @param string $object The object type (always "list").
     */
    public function __construct(
        public string $id,
        public string $model,
        public array $data,
        public EmbeddingsUsage $usage,
        public string $object = 'list',
    ) {
    }

    /**
     * Creates an EmbeddingsResponse from an API response array.
     *
     * @param array<string, mixed> $data The raw API response data.
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $embeddings = array_map(
            fn (array $embedding) => Embedding::fromArray($embedding),
            $data['data'] ?? [],
        );

        return new self(
            id: $data['id'] ?? '',
            model: $data['model'] ?? '',
            data: $embeddings,
            usage: EmbeddingsUsage::fromArray($data['usage'] ?? []),
            object: $data['object'] ?? 'list',
        );
    }

    /**
     * Gets the first embedding vector.
     *
     * Convenient method for single-input requests.
     *
     * @return array<float>|null The embedding vector or null if no embeddings.
     */
    public function getEmbedding(): ?array
    {
        if ($this->data === []) {
            return null;
        }

        return $this->data[0]->embedding;
    }

    /**
     * Gets all embedding vectors.
     *
     * @return array<array<float>> Array of embedding vectors.
     */
    public function getEmbeddings(): array
    {
        return array_map(
            fn (Embedding $embedding) => $embedding->embedding,
            $this->data,
        );
    }

    /**
     * Gets the number of embeddings in the response.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->data);
    }
}
