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
 * Embeddings Example
 * ==================
 *
 * This example demonstrates how to use the embeddings API to generate
 * vector representations of text. Embeddings are useful for:
 * - Semantic similarity comparison
 * - Text classification
 * - Clustering documents
 * - Search and retrieval
 *
 * IMPORTANT: As of January 2026, the xAI embeddings API endpoint exists
 * but no embedding models are publicly available yet. This example will
 * return a 404 error until xAI releases embedding models. Check the xAI
 * documentation for current model availability.
 *
 * Usage:
 *   php examples/embeddings.php                    # Basic single embedding
 *   php examples/embeddings.php --batch            # Batch embeddings
 *   php examples/embeddings.php --similarity       # Similarity comparison
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\XaiClient;

// Parse command line arguments
$options = getopt('', ['batch', 'similarity', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
        xAI PHP SDK - Embeddings Example

        Usage: php examples/embeddings.php [OPTIONS]

        Options:
          --batch       Generate embeddings for multiple texts at once
          --similarity  Compare semantic similarity between texts
          --help        Show this help message

        Environment:
          XAI_API_KEY   Your xAI API key (required)

        Examples:
          php examples/embeddings.php
          php examples/embeddings.php --batch
          php examples/embeddings.php --similarity

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

/**
 * Calculate cosine similarity between two vectors.
 *
 * @param array<float> $a First vector
 * @param array<float> $b Second vector
 *
 * @return float Cosine similarity (-1 to 1, higher means more similar)
 */
function cosineSimilarity(array $a, array $b): float
{
    $dotProduct = 0.0;
    $normA = 0.0;
    $normB = 0.0;

    for ($i = 0; $i < count($a); $i++) {
        $dotProduct += $a[$i] * $b[$i];
        $normA += $a[$i] * $a[$i];
        $normB += $b[$i] * $b[$i];
    }

    if ($normA === 0.0 || $normB === 0.0) {
        return 0.0;
    }

    return $dotProduct / (sqrt($normA) * sqrt($normB));
}

echo "xAI Embeddings Example\n";
echo "======================\n\n";

try {
    if (isset($options['similarity'])) {
        // Similarity comparison example
        echo "Comparing semantic similarity between texts...\n\n";

        $texts = [
            'The cat sat on the mat.',
            'A feline rested on the rug.',
            'The stock market crashed today.',
            'Dogs are great pets.',
        ];

        // Get embeddings for all texts
        $response = $client->embeddings->create(
            model: 'v1',
            input: $texts,
        );

        echo 'Generated ' . $response->count() . " embeddings\n";
        echo "Model: {$response->model}\n";
        echo 'Dimensions: ' . ($response->data[0]?->getDimensions() ?? 'N/A') . "\n\n";

        // Compare all pairs
        echo "Similarity Matrix:\n";
        echo str_repeat('-', 60) . "\n";

        $embeddings = $response->getEmbeddings();

        for ($i = 0; $i < count($texts); $i++) {
            for ($j = $i + 1; $j < count($texts); $j++) {
                $similarity = cosineSimilarity($embeddings[$i], $embeddings[$j]);
                echo sprintf(
                    "Text %d vs Text %d: %.4f\n  \"%s\"\n  \"%s\"\n\n",
                    $i + 1,
                    $j + 1,
                    $similarity,
                    substr($texts[$i], 0, 50),
                    substr($texts[$j], 0, 50),
                );
            }
        }
    } elseif (isset($options['batch'])) {
        // Batch embeddings example
        echo "Generating batch embeddings...\n\n";

        $texts = [
            'Hello, world!',
            'How are you today?',
            'The quick brown fox jumps over the lazy dog.',
            'Machine learning is transforming technology.',
            'PHP is a powerful programming language.',
        ];

        $response = $client->embeddings->create(
            model: 'v1',
            input: $texts,
        );

        echo 'Generated ' . $response->count() . " embeddings\n";
        echo "Model: {$response->model}\n";
        echo "Total tokens: {$response->usage->totalTokens}\n";
        echo "Prompt tokens: {$response->usage->promptTokens}\n\n";

        foreach ($response->data as $embedding) {
            echo sprintf(
                "Embedding %d: %d dimensions (first 5: [%.4f, %.4f, %.4f, %.4f, %.4f...])\n",
                $embedding->index + 1,
                $embedding->getDimensions(),
                $embedding->embedding[0] ?? 0,
                $embedding->embedding[1] ?? 0,
                $embedding->embedding[2] ?? 0,
                $embedding->embedding[3] ?? 0,
                $embedding->embedding[4] ?? 0,
            );
        }
    } else {
        // Single embedding example
        echo "Generating single embedding...\n\n";

        $text = 'The quick brown fox jumps over the lazy dog.';

        $response = $client->embeddings->create(
            model: 'v1',
            input: $text,
        );

        echo "Input: \"{$text}\"\n";
        echo "Model: {$response->model}\n";
        echo "Object: {$response->object}\n";
        echo "Prompt tokens: {$response->usage->promptTokens}\n";
        echo "Total tokens: {$response->usage->totalTokens}\n\n";

        $embedding = $response->getEmbedding();

        if ($embedding !== null) {
            echo sprintf("Embedding dimensions: %d\n", count($embedding));
            echo sprintf(
                "First 10 values: [%.6f, %.6f, %.6f, %.6f, %.6f, %.6f, %.6f, %.6f, %.6f, %.6f...]\n",
                $embedding[0] ?? 0,
                $embedding[1] ?? 0,
                $embedding[2] ?? 0,
                $embedding[3] ?? 0,
                $embedding[4] ?? 0,
                $embedding[5] ?? 0,
                $embedding[6] ?? 0,
                $embedding[7] ?? 0,
                $embedding[8] ?? 0,
                $embedding[9] ?? 0,
            );
        }
    }

    echo "\nDone!\n";
} catch (Displace\XaiSdk\Exceptions\XaiException $e) {
    echo "Error: {$e->getMessage()}\n";

    if ($e->getHttpStatusCode() !== null) {
        echo "HTTP Status: {$e->getHttpStatusCode()}\n";
    }

    // Provide helpful context for common errors
    if ($e->getHttpStatusCode() === 404) {
        echo "\nNote: The xAI embeddings API endpoint may not be publicly available yet.\n";
        echo "Check https://docs.x.ai/docs for current model availability.\n";
    }
    exit(1);
}
