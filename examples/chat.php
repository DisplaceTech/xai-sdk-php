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
 * Basic Chat Example
 * ==================
 *
 * This example demonstrates basic chat functionality with the xAI API.
 * It shows how to:
 * - Create a client and chat session
 * - Have multi-turn conversations
 * - Use streaming responses
 * - Sample multiple responses at once
 *
 * Usage:
 *   php examples/chat.php                  # Basic chat
 *   php examples/chat.php --stream         # Streaming chat
 *   php examples/chat.php --n=3            # Multiple responses
 *   php examples/chat.php --stream --n=3   # Streaming with multiple responses
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\XaiClient;

use function Displace\XaiSdk\Chat\assistant;
use function Displace\XaiSdk\Chat\system;
use function Displace\XaiSdk\Chat\user;

// Parse command line arguments
$options = getopt('', ['stream', 'n::', 'help']);
$streaming = isset($options['stream']);
$n = isset($options['n']) ? (int) $options['n'] : 1;

if (isset($options['help'])) {
    echo <<<HELP
xAI PHP SDK - Chat Example

Usage: php examples/chat.php [OPTIONS]

Options:
  --stream      Enable streaming responses
  --n=<count>   Number of responses to generate (default: 1)
  --help        Show this help message

Environment:
  XAI_API_KEY   Your xAI API key (required)

Examples:
  php examples/chat.php
  php examples/chat.php --stream
  php examples/chat.php --n=3
  php examples/chat.php --stream --n=3

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

// Create a chat session with initial messages
$chat = $client->chat->create(
    model: 'grok-3',
    messages: [
        system('You talk like a pirate.'),
        user('How are you?'),
        assistant('Ahoy! I be doing mighty fine, matey!'),
    ]
);

echo "xAI Chat Example\n";
echo "================\n";
echo "Mode: " . ($streaming ? 'Streaming' : 'Basic') . ($n > 1 ? " (n={$n})" : '') . "\n";
echo "Type 'exit' to quit.\n\n";

/**
 * Basic chat without streaming.
 */
function basicChat(\Displace\XaiSdk\Chat\Chat $chat): void
{
    while (true) {
        echo "You: ";
        $prompt = trim((string) fgets(STDIN));

        if (strtolower($prompt) === 'exit') {
            break;
        }

        if ($prompt === '') {
            continue;
        }

        // Add user message
        $chat->append(user($prompt));

        // Get response
        $response = $chat->sample();
        echo "Grok: {$response->getContent()}\n\n";

        // Add assistant response to history
        $chat->append($response);
    }
}

/**
 * Chat with streaming responses.
 */
function chatWithStreaming(\Displace\XaiSdk\Chat\Chat $chat): void
{
    while (true) {
        echo "You: ";
        $prompt = trim((string) fgets(STDIN));

        if (strtolower($prompt) === 'exit') {
            break;
        }

        if ($prompt === '') {
            continue;
        }

        // Add user message
        $chat->append(user($prompt));

        echo "Grok: ";

        // Stream the response
        $lastResponse = null;
        foreach ($chat->stream() as [$response, $chunk]) {
            echo $chunk->content;
            $lastResponse = $response;
        }
        echo "\n\n";

        // Add the complete response to history
        if ($lastResponse !== null) {
            $chat->append($lastResponse);
        }
    }
}

/**
 * Batch chat - generate multiple responses.
 */
function batchChat(\Displace\XaiSdk\Chat\Chat $chat, int $n): void
{
    while (true) {
        echo "You: ";
        $prompt = trim((string) fgets(STDIN));

        if (strtolower($prompt) === 'exit') {
            break;
        }

        if ($prompt === '') {
            continue;
        }

        // Add user message
        $chat->append(user($prompt));

        // Get multiple responses
        $responses = $chat->sampleBatch($n);

        foreach ($responses as $index => $response) {
            echo "Grok (response " . ($index + 1) . "): {$response->getContent()}\n";
        }
        echo "\n";

        // Add only the first response to history
        $chat->append($responses[0]);
    }
}

// Run the appropriate chat mode
try {
    match ([$n > 1, $streaming]) {
        [false, false] => basicChat($chat),
        [false, true] => chatWithStreaming($chat),
        [true, false] => batchChat($chat, $n),
        [true, true] => batchChat($chat, $n), // Streaming batch not implemented yet
    };
} catch (\Displace\XaiSdk\Exceptions\XaiException $e) {
    echo "\nError: {$e->getMessage()}\n";
    if ($e->getHttpStatusCode() !== null) {
        echo "HTTP Status: {$e->getHttpStatusCode()}\n";
    }
    exit(1);
}

echo "Goodbye!\n";
