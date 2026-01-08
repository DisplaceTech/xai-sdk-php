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
 * Represents a document in a collection.
 */
readonly class Document
{
    /**
     * Document statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_INDEXED = 'indexed';
    public const STATUS_FAILED = 'failed';

    /**
     * Creates a new Document instance.
     *
     * @param File $file The file metadata.
     * @param string $status The document indexing status.
     * @param array<string, mixed> $fields Custom fields attached to the document.
     * @param string|null $statusMessage Additional status message.
     * @param int $chunksCount Number of chunks the document was split into.
     */
    public function __construct(
        public File $file,
        public string $status = self::STATUS_PENDING,
        public array $fields = [],
        public ?string $statusMessage = null,
        public int $chunksCount = 0,
    ) {}

    /**
     * Checks if the document is indexed and ready for search.
     */
    public function isIndexed(): bool
    {
        return $this->status === self::STATUS_INDEXED;
    }

    /**
     * Checks if the document is still being processed.
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PENDING || $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Checks if indexing failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Creates a Document from an API response array.
     *
     * @param array<string, mixed> $data The document data.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $fileData = $data['file_metadata'] ?? $data['file'] ?? $data;
        $file = File::fromArray($fileData);

        return new self(
            file: $file,
            status: $data['status'] ?? self::STATUS_PENDING,
            fields: $data['fields'] ?? [],
            statusMessage: $data['status_message'] ?? null,
            chunksCount: $data['chunks_count'] ?? 0,
        );
    }
}
