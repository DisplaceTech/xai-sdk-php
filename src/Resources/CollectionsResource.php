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

namespace Displace\XaiSdk\Resources;

use Displace\XaiSdk\Http\HttpClient;
use Displace\XaiSdk\Responses\Collection;
use Displace\XaiSdk\Responses\Document;
use Displace\XaiSdk\Responses\SearchResult;
use RuntimeException;

/**
 * Resource for collections API (RAG).
 *
 * This class provides access to the collections endpoint, allowing you to create
 * and manage document collections for retrieval-augmented generation (RAG).
 *
 * @example
 * ```php
 * // Create a collection
 * $collection = $client->collections->create(
 *     name: 'research-papers',
 *     modelName: 'grok-embedding-small'
 * );
 *
 * // Upload a document
 * $document = $client->collections->uploadDocument(
 *     collectionId: $collection->id,
 *     name: 'paper.pdf',
 *     data: file_get_contents('paper.pdf')
 * );
 *
 * // Search the collection
 * $results = $client->collections->search(
 *     query: 'What is machine learning?',
 *     collectionIds: [$collection->id]
 * );
 * ```
 */
class CollectionsResource
{
    /**
     * Retrieval modes.
     */
    public const MODE_HYBRID = 'hybrid';

    public const MODE_SEMANTIC = 'semantic';

    public const MODE_KEYWORD = 'keyword';

    private const ENDPOINT = '/collections';

    /**
     * Creates a new CollectionsResource instance.
     *
     * @param HttpClient $httpClient The HTTP client for making requests.
     */
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {
    }

    /**
     * Creates a new collection.
     *
     * @param string $name The collection name.
     * @param string $modelName The embedding model to use.
     * @param array<string, mixed>|null $chunkConfiguration Chunk configuration settings.
     * @param array<array<string, mixed>>|null $fieldDefinitions Field definitions for documents.
     *
     * @return Collection The created collection.
     */
    public function create(
        string $name,
        string $modelName = 'grok-embedding-small',
        ?array $chunkConfiguration = null,
        ?array $fieldDefinitions = null,
    ): Collection {
        $payload = [
            'name' => $name,
            'model_name' => $modelName,
        ];

        if ($chunkConfiguration !== null) {
            $payload['chunk_configuration'] = $chunkConfiguration;
        }

        if ($fieldDefinitions !== null) {
            $payload['field_definitions'] = $fieldDefinitions;
        }

        $response = $this->httpClient->post(self::ENDPOINT, $payload);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return Collection::fromArray($data);
    }

    /**
     * Lists all collections.
     *
     * @param int $limit Maximum number of collections to return.
     * @param string $order Sort order ('asc' or 'desc').
     * @param string $sortBy Field to sort by ('name', 'age').
     * @param string|null $after Pagination token.
     *
     * @return array{collections: array<Collection>, pagination_token: string|null}
     */
    public function list(
        int $limit = 20,
        string $order = 'asc',
        string $sortBy = 'name',
        ?string $after = null,
    ): array {
        $query = [
            'limit' => $limit,
            'order' => $order,
            'sort_by' => $sortBy,
        ];

        if ($after !== null) {
            $query['pagination_token'] = $after;
        }

        $response = $this->httpClient->get(self::ENDPOINT, $query);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $collections = array_map(
            fn (array $item) => Collection::fromArray($item),
            $data['collections'] ?? $data['data'] ?? [],
        );

        return [
            'collections' => $collections,
            'pagination_token' => $data['pagination_token'] ?? null,
        ];
    }

    /**
     * Gets a collection by ID.
     *
     * @param string $collectionId The collection ID.
     *
     * @return Collection The collection.
     */
    public function get(string $collectionId): Collection
    {
        $response = $this->httpClient->get(self::ENDPOINT . '/' . $collectionId);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return Collection::fromArray($data);
    }

    /**
     * Updates a collection.
     *
     * @param string $collectionId The collection ID.
     * @param string|null $name New collection name.
     * @param array<string, mixed>|null $chunkConfiguration New chunk configuration.
     *
     * @return Collection The updated collection.
     */
    public function update(
        string $collectionId,
        ?string $name = null,
        ?array $chunkConfiguration = null,
    ): Collection {
        $payload = [];

        if ($name !== null) {
            $payload['name'] = $name;
        }

        if ($chunkConfiguration !== null) {
            $payload['chunk_configuration'] = $chunkConfiguration;
        }

        $response = $this->httpClient->patch(self::ENDPOINT . '/' . $collectionId, $payload);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return Collection::fromArray($data);
    }

    /**
     * Deletes a collection.
     *
     * @param string $collectionId The collection ID.
     *
     * @return bool True if deleted successfully.
     */
    public function delete(string $collectionId): bool
    {
        $this->httpClient->delete(self::ENDPOINT . '/' . $collectionId);

        return true;
    }

    /**
     * Uploads a document to a collection.
     *
     * @param string $collectionId The collection ID.
     * @param string $name The document name.
     * @param string $data The document content.
     * @param string|null $contentType The content type.
     * @param array<string, mixed>|null $fields Custom fields for the document.
     * @param bool $waitForIndexing Whether to wait for indexing to complete.
     * @param int $pollIntervalSeconds Poll interval when waiting for indexing.
     * @param int $timeoutSeconds Timeout when waiting for indexing.
     *
     * @return Document The uploaded document.
     */
    public function uploadDocument(
        string $collectionId,
        string $name,
        string $data,
        ?string $contentType = null,
        ?array $fields = null,
        bool $waitForIndexing = false,
        int $pollIntervalSeconds = 1,
        int $timeoutSeconds = 60,
    ): Document {
        $payload = [
            'name' => $name,
            'data' => base64_encode($data),
        ];

        if ($contentType !== null) {
            $payload['content_type'] = $contentType;
        }

        if ($fields !== null) {
            $payload['fields'] = $fields;
        }

        $response = $this->httpClient->post(
            self::ENDPOINT . '/' . $collectionId . '/documents',
            $payload,
        );
        $body = $response->getBody();
        $body->rewind();
        $responseData = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $document = Document::fromArray($responseData);

        if ($waitForIndexing) {
            $document = $this->waitForIndexing(
                $collectionId,
                $document->file->id,
                $pollIntervalSeconds,
                $timeoutSeconds,
            );
        }

        return $document;
    }

    /**
     * Adds an existing file to a collection.
     *
     * @param string $collectionId The collection ID.
     * @param string $fileId The file ID to add.
     * @param array<string, mixed>|null $fields Custom fields for the document.
     *
     * @return Document The added document.
     */
    public function addExistingDocument(
        string $collectionId,
        string $fileId,
        ?array $fields = null,
    ): Document {
        $payload = [
            'file_id' => $fileId,
        ];

        if ($fields !== null) {
            $payload['fields'] = $fields;
        }

        $response = $this->httpClient->post(
            self::ENDPOINT . '/' . $collectionId . '/documents',
            $payload,
        );
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return Document::fromArray($data);
    }

    /**
     * Lists documents in a collection.
     *
     * @param string $collectionId The collection ID.
     * @param int $limit Maximum number of documents to return.
     * @param string $order Sort order ('asc' or 'desc').
     * @param string $sortBy Field to sort by ('name', 'age', 'size').
     * @param string|null $after Pagination token.
     *
     * @return array{documents: array<Document>, pagination_token: string|null}
     */
    public function listDocuments(
        string $collectionId,
        int $limit = 20,
        string $order = 'desc',
        string $sortBy = 'age',
        ?string $after = null,
    ): array {
        $query = [
            'limit' => $limit,
            'order' => $order,
            'sort_by' => $sortBy,
        ];

        if ($after !== null) {
            $query['pagination_token'] = $after;
        }

        $response = $this->httpClient->get(
            self::ENDPOINT . '/' . $collectionId . '/documents',
            $query,
        );
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $documents = array_map(
            fn (array $item) => Document::fromArray($item),
            $data['documents'] ?? $data['data'] ?? [],
        );

        return [
            'documents' => $documents,
            'pagination_token' => $data['pagination_token'] ?? null,
        ];
    }

    /**
     * Gets a document from a collection.
     *
     * @param string $fileId The file ID.
     * @param string $collectionId The collection ID.
     *
     * @return Document The document.
     */
    public function getDocument(string $fileId, string $collectionId): Document
    {
        $response = $this->httpClient->get(
            self::ENDPOINT . '/' . $collectionId . '/documents/' . $fileId,
        );
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return Document::fromArray($data);
    }

    /**
     * Gets multiple documents from a collection.
     *
     * @param string $collectionId The collection ID.
     * @param array<string> $fileIds The file IDs to retrieve.
     *
     * @return array<Document> The documents.
     */
    public function batchGetDocuments(string $collectionId, array $fileIds): array
    {
        $response = $this->httpClient->post(
            self::ENDPOINT . '/' . $collectionId . '/documents/batch',
            ['file_ids' => $fileIds],
        );
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return array_map(
            fn (array $item) => Document::fromArray($item),
            $data['documents'] ?? $data['data'] ?? [],
        );
    }

    /**
     * Updates a document in a collection.
     *
     * @param string $collectionId The collection ID.
     * @param string $fileId The file ID.
     * @param string|null $name New document name.
     * @param string|null $data New document content.
     * @param string|null $contentType New content type.
     * @param array<string, mixed>|null $fields Updated fields.
     *
     * @return Document The updated document.
     */
    public function updateDocument(
        string $collectionId,
        string $fileId,
        ?string $name = null,
        ?string $data = null,
        ?string $contentType = null,
        ?array $fields = null,
    ): Document {
        $payload = [];

        if ($name !== null) {
            $payload['name'] = $name;
        }

        if ($data !== null) {
            $payload['data'] = base64_encode($data);
        }

        if ($contentType !== null) {
            $payload['content_type'] = $contentType;
        }

        if ($fields !== null) {
            $payload['fields'] = $fields;
        }

        $response = $this->httpClient->patch(
            self::ENDPOINT . '/' . $collectionId . '/documents/' . $fileId,
            $payload,
        );
        $body = $response->getBody();
        $body->rewind();
        $responseData = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return Document::fromArray($responseData);
    }

    /**
     * Removes a document from a collection.
     *
     * @param string $collectionId The collection ID.
     * @param string $fileId The file ID to remove.
     *
     * @return bool True if removed successfully.
     */
    public function removeDocument(string $collectionId, string $fileId): bool
    {
        $this->httpClient->delete(
            self::ENDPOINT . '/' . $collectionId . '/documents/' . $fileId,
        );

        return true;
    }

    /**
     * Reindexes a document.
     *
     * @param string $collectionId The collection ID.
     * @param string $fileId The file ID to reindex.
     *
     * @return Document The document.
     */
    public function reindexDocument(string $collectionId, string $fileId): Document
    {
        $response = $this->httpClient->post(
            self::ENDPOINT . '/' . $collectionId . '/documents/' . $fileId . '/reindex',
            [],
        );
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return Document::fromArray($data);
    }

    /**
     * Searches across collections.
     *
     * @param string $query The search query.
     * @param array<string> $collectionIds The collection IDs to search.
     * @param int $limit Maximum number of results.
     * @param string|null $instructions Additional instructions for the search.
     * @param string $retrievalMode The retrieval mode (hybrid, semantic, keyword).
     *
     * @return array{matches: array<SearchResult>}
     */
    public function search(
        string $query,
        array $collectionIds,
        int $limit = 10,
        ?string $instructions = null,
        string $retrievalMode = self::MODE_HYBRID,
    ): array {
        $payload = [
            'query' => $query,
            'collection_ids' => $collectionIds,
            'limit' => $limit,
            'retrieval_mode' => $retrievalMode,
        ];

        if ($instructions !== null) {
            $payload['instructions'] = $instructions;
        }

        $response = $this->httpClient->post(self::ENDPOINT . '/search', $payload);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $matches = array_map(
            fn (array $item) => SearchResult::fromArray($item),
            $data['matches'] ?? $data['results'] ?? [],
        );

        return [
            'matches' => $matches,
        ];
    }

    /**
     * Waits for a document to be indexed.
     *
     * @param string $collectionId The collection ID.
     * @param string $fileId The file ID.
     * @param int $pollIntervalSeconds Poll interval in seconds.
     * @param int $timeoutSeconds Timeout in seconds.
     *
     * @throws RuntimeException If timeout is exceeded.
     *
     * @return Document The indexed document.
     */
    private function waitForIndexing(
        string $collectionId,
        string $fileId,
        int $pollIntervalSeconds,
        int $timeoutSeconds,
    ): Document {
        $startTime = time();

        while (true) {
            $document = $this->getDocument($fileId, $collectionId);

            if ($document->isIndexed()) {
                return $document;
            }

            if ($document->hasFailed()) {
                throw new RuntimeException(
                    'Document indexing failed: ' . ($document->statusMessage ?? 'Unknown error'),
                );
            }

            if ((time() - $startTime) >= $timeoutSeconds) {
                throw new RuntimeException(
                    "Timeout waiting for document indexing after {$timeoutSeconds} seconds",
                );
            }

            sleep($pollIntervalSeconds);
        }
    }
}
