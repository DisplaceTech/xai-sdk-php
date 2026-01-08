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
 * Represents a document collection for RAG.
 */
readonly class Collection
{
    /**
     * Creates a new Collection instance.
     *
     * @param string $id The unique collection identifier.
     * @param string $name The collection name.
     * @param string $modelName The embedding model used for indexing.
     * @param int $documentsCount Number of documents in the collection.
     * @param DateTimeImmutable $createdAt When the collection was created.
     * @param DateTimeImmutable|null $updatedAt When the collection was last updated.
     * @param array<string, mixed> $chunkConfiguration Chunk configuration settings.
     * @param array<string, mixed> $indexConfiguration Index configuration settings.
     * @param array<array<string, mixed>> $fieldDefinitions Field definitions for documents.
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $modelName = 'grok-embedding-small',
        public int $documentsCount = 0,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $updatedAt = null,
        public array $chunkConfiguration = [],
        public array $indexConfiguration = [],
        public array $fieldDefinitions = [],
    ) {
    }

    /**
     * Creates a Collection from an API response array.
     *
     * @param array<string, mixed> $data The collection data.
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $createdAt = new DateTimeImmutable();

        if (isset($data['created_at'])) {
            $createdAt = is_int($data['created_at'])
                ? $createdAt->setTimestamp($data['created_at'])
                : new DateTimeImmutable($data['created_at']);
        }

        $updatedAt = null;

        if (isset($data['updated_at'])) {
            $updatedAt = is_int($data['updated_at'])
                ? new DateTimeImmutable()->setTimestamp($data['updated_at'])
                : new DateTimeImmutable($data['updated_at']);
        }

        // Extract model name from index configuration if present
        $modelName = $data['model_name'] ?? 'grok-embedding-small';

        if (isset($data['index_configuration']['model_name'])) {
            $modelName = $data['index_configuration']['model_name'];
        }

        return new self(
            id: $data['collection_id'] ?? $data['id'] ?? '',
            name: $data['collection_name'] ?? $data['name'] ?? '',
            modelName: $modelName,
            documentsCount: $data['documents_count'] ?? 0,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            chunkConfiguration: $data['chunk_configuration'] ?? [],
            indexConfiguration: $data['index_configuration'] ?? [],
            fieldDefinitions: $data['field_definitions'] ?? [],
        );
    }

    /**
     * Checks if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->documentsCount === 0;
    }
}
