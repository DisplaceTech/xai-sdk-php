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
 * Streaming Example
 * =================
 *
 * This example demonstrates streaming responses from the xAI API.
 * Streaming allows you to receive tokens as they're generated,
 * providing a more responsive user experience.
 *
 * Usage:
 *   php examples/streaming.php
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\XaiClient;

use function Displace\XaiSdk\Chat\system;
use function Displace\XaiSdk\Chat\user;

// Create the client
try {
    $client = new XaiClient();
} catch (RuntimeException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Please set the XAI_API_KEY environment variable.\n";
    exit(1);
}

echo "xAI Streaming Example\n";
echo "=====================\n\n";

// Create a chat session
$chat = $client->chat->create(
    model: 'grok-3',
    messages: [
        system('You are a helpful assistant. Give detailed explanations.'),
    ]
);

// Add a user message that will generate a longer response
$chat->append(user('Explain how photosynthesis works in about 100 words.'));

echo "Streaming response:\n\n";

// Stream the response
$startTime = microtime(true);
$tokenCount = 0;

foreach ($chat->stream() as [$response, $chunk]) {
    // Print each token as it arrives
    echo $chunk->content;
    $tokenCount++;

    // Show progress indicator
    if ($chunk->finishReason !== null) {
        $duration = microtime(true) - $startTime;
        echo "\n\n";
        echo "---\n";
        echo "Finish reason: {$chunk->finishReason}\n";
        echo sprintf("Duration: %.2f seconds\n", $duration);
        echo "Tokens: {$response->usage->completionTokens}\n";
    }
}

echo "\n";

// Continue the conversation
echo "Continuing conversation...\n\n";

$chat->append($response);
$chat->append(user('Now explain cellular respiration in the same detail.'));

echo "Streaming second response:\n\n";

foreach ($chat->stream() as [$response, $chunk]) {
    echo $chunk->content;

    if ($chunk->finishReason !== null) {
        echo "\n\n";
        echo "---\n";
        echo "Finish reason: {$chunk->finishReason}\n";
        echo "Total tokens used: {$response->usage->totalTokens}\n";
    }
}

echo "\nDone!\n";
