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
 * Represents a search result match from a collection query.
 */
readonly class SearchResult
{
    /**
     * Creates a new SearchResult instance.
     *
     * @param string $fileId The ID of the file that matched.
     * @param string $collectionId The ID of the collection containing the match.
     * @param float $score The relevance score (higher is better).
     * @param string $chunkContent The content of the matching chunk.
     * @param int $chunkIndex The index of this chunk within the document.
     * @param array<string, mixed> $metadata Additional metadata about the match.
     * @param string|null $filename The filename of the matching document.
     */
    public function __construct(
        public string $fileId,
        public string $collectionId,
        public float $score,
        public string $chunkContent,
        public int $chunkIndex = 0,
        public array $metadata = [],
        public ?string $filename = null,
    ) {}

    /**
     * Gets a preview of the chunk content.
     *
     * @param int $maxLength Maximum length of the preview.
     * @return string The preview text.
     */
    public function getPreview(int $maxLength = 200): string
    {
        if (mb_strlen($this->chunkContent) <= $maxLength) {
            return $this->chunkContent;
        }

        return mb_substr($this->chunkContent, 0, $maxLength) . '...';
    }

    /**
     * Creates a SearchResult from an API response array.
     *
     * @param array<string, mixed> $data The match data.
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fileId: $data['file_id'] ?? '',
            collectionId: $data['collection_id'] ?? '',
            score: (float) ($data['score'] ?? 0.0),
            chunkContent: $data['chunk_content'] ?? $data['content'] ?? '',
            chunkIndex: $data['chunk_index'] ?? 0,
            metadata: $data['metadata'] ?? [],
            filename: $data['filename'] ?? null,
        );
    }
}
