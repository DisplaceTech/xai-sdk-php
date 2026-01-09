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
 * Reasoning Example
 * =================
 *
 * This example demonstrates how to use reasoning models with the xAI API.
 * Reasoning models can show their thought process before providing a final answer,
 * which is useful for complex problem-solving tasks.
 *
 * Usage:
 *   php examples/reasoning.php                    # Low effort reasoning
 *   php examples/reasoning.php --effort=high      # High effort reasoning
 *   php examples/reasoning.php --stream           # Streaming mode
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\XaiClient;

use function Displace\XaiSdk\Chat\user;

// Parse command line arguments
$options = getopt('', ['stream', 'effort::', 'help', 'test']);
$streaming = isset($options['stream']);
$effort = $options['effort'] ?? 'low';
$testMode = isset($options['test']);

if (isset($options['help'])) {
    echo <<<HELP
        xAI PHP SDK - Reasoning Example

        Usage: php examples/reasoning.php [OPTIONS]

        Options:
          --stream         Enable streaming responses
          --effort=<level> Reasoning effort level: low or high (default: low)
          --test           Run a single test exchange (non-interactive)
          --help           Show this help message

        Environment:
          XAI_API_KEY   Your xAI API key (required)

        Example prompts to try:
          "Solve: What is 15% of 847?"
          "If all roses are flowers and some flowers fade quickly, can we conclude that some roses fade quickly?"
          "A train leaves station A at 9:00 AM traveling at 60 mph..."

        HELP;
    exit(0);
}

// Validate effort level
if (! in_array($effort, ['low', 'high'], true)) {
    echo "Error: Invalid effort level. Use 'low' or 'high'.\n";
    exit(1);
}

// Create the client
try {
    $client = new XaiClient();
} catch (RuntimeException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Please set the XAI_API_KEY environment variable.\n";
    exit(1);
}

echo "xAI Reasoning Example\n";
echo "=====================\n";
echo "Model: grok-3-mini (reasoning model)\n";
echo "Effort: {$effort}\n";
echo 'Mode: ' . ($streaming ? 'Streaming' : 'Basic') . "\n\n";

// Create chat with reasoning configuration
$chat = $client->chat->create(
    model: 'grok-3-mini',
    reasoningEffort: $effort,
);

// Get the prompt
if ($testMode) {
    $prompt = 'What is 15% of 200?';
    echo "Test prompt: {$prompt}\n";
} else {
    echo 'Enter a prompt: ';
    $prompt = trim((string) fgets(STDIN));

    if ($prompt === '') {
        echo "No prompt provided.\n";
        exit(1);
    }
}

$chat->append(user($prompt));

try {
    if ($streaming) {
        // Streaming mode
        echo "\n--------- Reasoning ---------\n";

        $firstContent = true;
        $latestResponse = null;

        foreach ($chat->stream() as [$response, $chunk]) {
            // Print reasoning content
            if ($chunk->reasoningContent !== null && $chunk->reasoningContent !== '') {
                echo $chunk->reasoningContent;
            }

            // Print final content
            if ($chunk->content !== '') {
                if ($firstContent) {
                    echo "\n\n--------- Final Response ---------\n";
                    $firstContent = false;
                }
                echo $chunk->content;
            }

            $latestResponse = $response;
        }

        if ($latestResponse !== null) {
            echo "\n\n--------- Usage ---------\n";
            echo "Reasoning Tokens: {$latestResponse->usage->reasoningTokens}\n";
            echo "Completion Tokens: {$latestResponse->usage->completionTokens}\n";
            echo "Total Tokens: {$latestResponse->usage->totalTokens}\n";
        }
    } else {
        // Non-streaming mode
        $response = $chat->sample();

        echo "\n--------- Reasoning ---------\n";
        $reasoningContent = $response->getReasoningContent();

        if ($reasoningContent !== null) {
            echo "{$reasoningContent}\n";
        } else {
            echo "(No reasoning content available)\n";
        }

        echo "\n--------- Final Response ---------\n";
        echo $response->getContent();

        echo "\n\n--------- Usage ---------\n";
        echo "Reasoning Tokens: {$response->usage->reasoningTokens}\n";
        echo "Completion Tokens: {$response->usage->completionTokens}\n";
        echo "Total Tokens: {$response->usage->totalTokens}\n";
    }
    if ($testMode) {
        echo "\n\nTest passed!\n";
    }
} catch (Displace\XaiSdk\Exceptions\XaiException $e) {
    echo "\nError: {$e->getMessage()}\n";

    if ($e->getHttpStatusCode() !== null) {
        echo "HTTP Status: {$e->getHttpStatusCode()}\n";
    }
    exit(1);
}
