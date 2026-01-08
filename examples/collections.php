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
 *
 * Collections Example (RAG)
 * =========================
 *
 * This example demonstrates how to use the collections API for retrieval-augmented
 * generation (RAG). Collections allow you to upload documents, index them, and
 * search across them using semantic, keyword, or hybrid search.
 *
 * Usage:
 *   php examples/collections.php                # Run full demo
 *   php examples/collections.php --help         # Show help
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\Resources\CollectionsResource;
use Displace\XaiSdk\XaiClient;

// Parse command line arguments
$options = getopt('', ['help']);

if (isset($options['help'])) {
    echo <<<HELP
        xAI PHP SDK - Collections Example (RAG)

        Usage: php examples/collections.php

        This example demonstrates the full collections API workflow:
        1. Creating collections with different configurations
        2. Uploading and managing documents
        3. Searching across collections
        4. Cleaning up resources

        Environment:
          XAI_API_KEY   Your xAI API key (required)

        Note: This example will create and delete test collections.

        HELP;
    exit(0);
}

// Create the client
try {
    $client = new XaiClient();
} catch (RuntimeException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Please set the XAI_API_KEY environment variable.\n";
    exit(1);
}

echo "xAI Collections Example (RAG)\n";
echo "=============================\n\n";

/**
 * Creates a basic collection.
 */
function createCollectionExample(XaiClient $client): string
{
    echo "=== Create Collection Example ===\n\n";

    $collection = $client->collections->create(
        name: 'research-papers-' . uniqid(),
        modelName: 'grok-embedding-small',
    );

    echo "Created collection: {$collection->name}\n";
    echo "Collection ID: {$collection->id}\n";
    echo "Model: {$collection->modelName}\n";
    echo 'Created at: ' . $collection->createdAt->format('Y-m-d H:i:s') . "\n\n";

    return $collection->id;
}

/**
 * Creates a collection with token-based chunking configuration.
 */
function createCollectionWithChunking(XaiClient $client): string
{
    echo "=== Create Collection with Token-Based Chunking ===\n\n";

    $collection = $client->collections->create(
        name: 'code-snippets-' . uniqid(),
        modelName: 'grok-embedding-small',
        chunkConfiguration: [
            'tokens_configuration' => [
                'max_chunk_size_tokens' => 500,
                'chunk_overlap_tokens' => 50,
                'encoding_name' => 'cl100k_base',
            ],
            'strip_whitespace' => false,
        ],
    );

    echo "Created collection: {$collection->name}\n";
    echo "Collection ID: {$collection->id}\n\n";

    return $collection->id;
}

/**
 * Lists all collections.
 */
function listCollectionsExample(XaiClient $client): void
{
    echo "=== List Collections Example ===\n\n";

    $result = $client->collections->list(
        limit: 10,
        order: 'asc',
        sortBy: 'name',
    );

    echo 'Found ' . count($result['collections']) . " collections (sorted by name):\n";

    foreach ($result['collections'] as $collection) {
        echo "  - {$collection->name} (ID: {$collection->id})\n";
        echo "    Documents: {$collection->documentsCount}\n";
    }

    if ($result['pagination_token'] !== null) {
        echo "\nPagination token available for next page.\n";
    }
    echo "\n";
}

/**
 * Gets collection details.
 */
function getCollectionExample(XaiClient $client, string $collectionId): void
{
    echo "=== Get Collection Metadata Example ===\n\n";

    $collection = $client->collections->get($collectionId);

    echo "Collection: {$collection->name}\n";
    echo "ID: {$collection->id}\n";
    echo "Documents: {$collection->documentsCount}\n";
    echo "Model: {$collection->modelName}\n";
    echo 'Created at: ' . $collection->createdAt->format('Y-m-d H:i:s') . "\n\n";
}

/**
 * Updates a collection.
 */
function updateCollectionExample(XaiClient $client, string $collectionId): void
{
    echo "=== Update Collection Example ===\n\n";

    $updated = $client->collections->update(
        collectionId: $collectionId,
        name: 'research-papers-updated-' . uniqid(),
        chunkConfiguration: [
            'chars_configuration' => [
                'max_chunk_size_chars' => 2000,
                'chunk_overlap_chars' => 200,
            ],
            'strip_whitespace' => true,
        ],
    );

    echo "Updated collection: {$updated->name}\n\n";
}

/**
 * Uploads a document to a collection.
 */
function uploadDocumentExample(XaiClient $client, string $collectionId): string
{
    echo "=== Upload Document Example ===\n\n";

    $documentContent = <<<'TEXT'
        Machine Learning Fundamentals

        Machine learning is a subset of artificial intelligence that focuses on building
        systems that can learn from data and improve their performance over time without
        being explicitly programmed. There are three main types of machine learning:

        1. Supervised Learning: The algorithm learns from labeled training data.
        2. Unsupervised Learning: The algorithm finds patterns in unlabeled data.
        3. Reinforcement Learning: The algorithm learns through trial and error.

        Common machine learning algorithms include:
        - Linear Regression
        - Decision Trees
        - Neural Networks
        - Support Vector Machines
        - K-Means Clustering
        TEXT;

    $document = $client->collections->uploadDocument(
        collectionId: $collectionId,
        name: 'ml-fundamentals.txt',
        data: $documentContent,
    );

    echo "Uploaded document: {$document->file->name}\n";
    echo "File ID: {$document->file->id}\n";
    echo "Size: {$document->file->sizeBytes} bytes\n";
    echo "Status: {$document->status}\n\n";

    return $document->file->id;
}

/**
 * Uploads a document and waits for indexing to complete.
 */
function uploadDocumentWithWaitExample(XaiClient $client, string $collectionId): string
{
    echo "=== Upload Document with Wait for Indexing ===\n\n";

    $documentContent = <<<'TEXT'
        Deep Learning Neural Networks

        Deep learning is a subset of machine learning that uses neural networks with
        multiple layers. These networks can learn hierarchical representations of data,
        making them particularly effective for tasks like image recognition and natural
        language processing.

        Key concepts in deep learning:
        - Layers: Input, hidden, and output layers
        - Activation Functions: ReLU, Sigmoid, Softmax
        - Backpropagation: Algorithm for training neural networks
        - Optimization: SGD, Adam, RMSprop
        - Regularization: Dropout, L1/L2 regularization
        TEXT;

    echo "Uploading document and waiting for indexing...\n";

    $document = $client->collections->uploadDocument(
        collectionId: $collectionId,
        name: 'deep-learning.txt',
        data: $documentContent,
        waitForIndexing: true,
        pollIntervalSeconds: 1,
        timeoutSeconds: 60,
    );

    echo "Document uploaded and indexed: {$document->file->name}\n";
    echo "File ID: {$document->file->id}\n";
    echo "Status: {$document->status}\n";
    echo "Document is ready to be searched immediately!\n\n";

    return $document->file->id;
}

/**
 * Lists documents in a collection.
 */
function listDocumentsExample(XaiClient $client, string $collectionId): void
{
    echo "=== List Documents Example ===\n\n";

    $result = $client->collections->listDocuments(
        collectionId: $collectionId,
        limit: 10,
        order: 'desc',
        sortBy: 'age',
    );

    echo 'Found ' . count($result['documents']) . " documents:\n";

    foreach ($result['documents'] as $doc) {
        echo "  - {$doc->file->name}\n";
        echo "    File ID: {$doc->file->id}\n";
        echo "    Size: {$doc->file->sizeBytes} bytes\n";
        echo "    Status: {$doc->status}\n";
    }
    echo "\n";
}

/**
 * Gets document metadata.
 */
function getDocumentExample(XaiClient $client, string $collectionId, string $fileId): void
{
    echo "=== Get Document Metadata Example ===\n\n";

    $document = $client->collections->getDocument($fileId, $collectionId);

    echo "Document: {$document->file->name}\n";
    echo "File ID: {$document->file->id}\n";
    echo "Size: {$document->file->sizeBytes} bytes\n";
    echo "Content type: {$document->file->contentType}\n";
    echo "Status: {$document->status}\n\n";
}

/**
 * Gets multiple documents in a batch.
 */
function batchGetDocumentsExample(XaiClient $client, string $collectionId, array $fileIds): void
{
    echo "=== Batch Get Documents Example ===\n\n";

    $documents = $client->collections->batchGetDocuments($collectionId, $fileIds);

    echo 'Retrieved ' . count($documents) . " documents:\n";

    foreach ($documents as $doc) {
        echo "  - {$doc->file->name} ({$doc->file->id})\n";
    }
    echo "\n";
}

/**
 * Updates a document.
 */
function updateDocumentExample(XaiClient $client, string $collectionId, string $fileId): void
{
    echo "=== Update Document Example ===\n\n";

    $newContent = 'Updated content with additional machine learning information.';

    $updated = $client->collections->updateDocument(
        collectionId: $collectionId,
        fileId: $fileId,
        name: 'ml-fundamentals-updated.txt',
        data: $newContent,
        contentType: 'text/plain',
    );

    echo "Updated document: {$updated->file->name}\n";
    echo "New size: {$updated->file->sizeBytes} bytes\n\n";
}

/**
 * Searches across collections.
 */
function searchExample(XaiClient $client, string $collectionId): void
{
    echo "=== Hybrid Search Example ===\n\n";

    $result = $client->collections->search(
        query: 'What is machine learning?',
        collectionIds: [$collectionId],
        limit: 5,
        instructions: 'Return concise, user-friendly explanations focused on core concepts.',
        retrievalMode: CollectionsResource::MODE_HYBRID,
    );

    echo 'Found ' . count($result['matches']) . " matches:\n\n";

    foreach ($result['matches'] as $i => $match) {
        echo '  Match ' . ($i + 1) . ":\n";
        echo "    File ID: {$match->fileId}\n";
        echo '    Score: ' . number_format($match->score, 4) . "\n";
        echo '    Content: ' . substr($match->chunkContent, 0, 100) . "...\n\n";
    }
}

/**
 * Reindexes a document.
 */
function reindexDocumentExample(XaiClient $client, string $collectionId, string $fileId): void
{
    echo "=== Reindex Document Example ===\n\n";

    $document = $client->collections->reindexDocument($collectionId, $fileId);
    echo "Reindexed document (file_id: {$fileId})\n";
    echo "Status: {$document->status}\n\n";
}

/**
 * Removes a document from a collection.
 */
function removeDocumentExample(XaiClient $client, string $collectionId, string $fileId): void
{
    echo "Removing document (file_id: {$fileId})...\n";
    $client->collections->removeDocument($collectionId, $fileId);
    echo "Document removed.\n";
}

/**
 * Deletes a collection.
 */
function deleteCollectionExample(XaiClient $client, string $collectionId): void
{
    echo "Deleting collection (ID: {$collectionId})...\n";
    $client->collections->delete($collectionId);
    echo "Collection deleted.\n";
}

// Run the examples
try {
    // Create collections
    $collectionId = createCollectionExample($client);
    $collectionId2 = createCollectionWithChunking($client);

    // List collections
    listCollectionsExample($client);

    // Get collection metadata
    getCollectionExample($client, $collectionId);

    // Update collection
    updateCollectionExample($client, $collectionId);

    // Upload documents
    $fileId = uploadDocumentExample($client, $collectionId);
    $fileIdWait = uploadDocumentWithWaitExample($client, $collectionId);
    $fileId2 = uploadDocumentExample($client, $collectionId);

    // List documents
    listDocumentsExample($client, $collectionId);

    // Get document metadata
    getDocumentExample($client, $collectionId, $fileId);

    // Batch get documents
    batchGetDocumentsExample($client, $collectionId, [$fileId, $fileId2]);

    // Update document
    updateDocumentExample($client, $collectionId, $fileId);

    // Search documents (wait a moment for indexing)
    echo "Waiting for indexing to complete before search...\n";
    sleep(3);
    searchExample($client, $collectionId);

    // Reindex document
    reindexDocumentExample($client, $collectionId, $fileId);

    // Cleanup
    echo "=== Cleanup ===\n\n";
    removeDocumentExample($client, $collectionId, $fileId);
    removeDocumentExample($client, $collectionId, $fileId2);
    removeDocumentExample($client, $collectionId, $fileIdWait);

    deleteCollectionExample($client, $collectionId);
    deleteCollectionExample($client, $collectionId2);

    echo "\n=== All examples completed successfully! ===\n";
} catch (Displace\XaiSdk\Exceptions\XaiException $e) {
    echo "\nError: {$e->getMessage()}\n";

    if ($e->getHttpStatusCode() !== null) {
        echo "HTTP Status: {$e->getHttpStatusCode()}\n";
    }
    exit(1);
}
