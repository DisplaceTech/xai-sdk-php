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
 * Function Calling Example
 * ========================
 *
 * This example demonstrates how to use function calling (tools) with the xAI API.
 * The model can decide to call functions you define, and you provide the results
 * back to continue the conversation.
 *
 * Usage:
 *   php examples/function_calling.php
 *   php examples/function_calling.php --stream
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\Tools\Tool;
use Displace\XaiSdk\XaiClient;

use function Displace\XaiSdk\Chat\system;
use function Displace\XaiSdk\Chat\toolResult;
use function Displace\XaiSdk\Chat\user;

// Parse command line arguments
$options = getopt('', ['stream', 'help', 'test']);
$streaming = isset($options['stream']);
$testMode = isset($options['test']);

if (isset($options['help'])) {
    echo <<<HELP
        xAI PHP SDK - Function Calling Example

        Usage: php examples/function_calling.php [OPTIONS]

        Options:
          --stream    Enable streaming responses
          --test      Run a single test exchange (non-interactive)
          --help      Show this help message

        Environment:
          XAI_API_KEY   Your xAI API key (required)

        Try asking:
          "What's the weather in New York?"
          "How about Tokyo in Celsius?"
          "Compare weather in London and Paris"

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

echo "xAI Function Calling Example\n";
echo "============================\n";
echo 'Mode: ' . ($streaming ? 'Streaming' : 'Basic') . ($testMode ? ' (test)' : '') . "\n";

/**
 * Simulated weather function.
 *
 * @param string $city The city name
 * @param string $units Temperature units (C or F)
 *
 * @return string Weather description
 */
function getWeather(string $city, string $units): string
{
    // Simulated weather data
    $temperature = $units === 'C' ? 20 : 68;

    return json_encode([
        'city' => $city,
        'temperature' => $temperature,
        'units' => $units,
        'conditions' => 'sunny',
        'description' => "The weather in {$city} is sunny at a temperature of {$temperature}°{$units}.",
    ]);
}

// Define the weather tool
$weatherTool = new Tool(
    name: 'get_weather',
    description: 'Get the current weather for a given city.',
    parameters: [
        'type' => 'object',
        'properties' => [
            'city' => [
                'type' => 'string',
                'description' => 'The name of the city to get the weather for.',
            ],
            'units' => [
                'type' => 'string',
                'description' => 'The temperature units to use.',
                'enum' => ['C', 'F'],
            ],
        ],
        'required' => ['city', 'units'],
    ],
);

// Create chat with tools
$chat = $client->chat->create(
    model: 'grok-3',
    messages: [
        system('You are a helpful weather assistant. When asked about weather, use the get_weather function to fetch accurate data. Always specify the units based on the user\'s preference or location conventions.'),
    ],
    tools: [$weatherTool],
);

// Test mode: run a single function call exchange and exit
if ($testMode) {
    try {
        $chat->append(user("What's the weather in London in Celsius?"));

        $response = $chat->sample();
        $chat->append($response);

        $toolCalls = $response->getToolCalls();

        if (empty($toolCalls)) {
            echo "Test failed: No tool calls were made.\n";
            exit(1);
        }

        echo "Tool calls made:\n";

        foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'] ?? '';
            $arguments = json_decode($toolCall['function']['arguments'] ?? '{}', true);
            echo "  - {$functionName}(" . json_encode($arguments) . ")\n";

            // Execute the function
            $result = match ($functionName) {
                'get_weather' => getWeather(
                    $arguments['city'] ?? 'Unknown',
                    $arguments['units'] ?? 'C',
                ),
                default => json_encode(['error' => "Unknown function: {$functionName}"]),
            };

            $chat->append(toolResult($result, $toolCall['id'] ?? ''));
        }

        // Get final response
        $finalResponse = $chat->sample();
        echo "Final response: {$finalResponse->getContent()}\n";
        echo "\nTest passed!\n";
        exit(0);
    } catch (Displace\XaiSdk\Exceptions\XaiException $e) {
        echo "Test failed: {$e->getMessage()}\n";

        if ($e->getHttpStatusCode() !== null) {
            echo "HTTP Status: {$e->getHttpStatusCode()}\n";
        }
        exit(1);
    }
}

echo "Type 'exit' to quit.\n\n";

/**
 * Process tool calls from a response.
 *
 * @param array<array<string, mixed>> $toolCalls The tool calls to process
 *
 * @return array<array{id: string, result: string}> Results for each tool call
 */
function processToolCalls(array $toolCalls): array
{
    $results = [];

    foreach ($toolCalls as $toolCall) {
        $functionName = $toolCall['function']['name'] ?? '';
        $arguments = json_decode($toolCall['function']['arguments'] ?? '{}', true);
        $callId = $toolCall['id'] ?? '';

        echo "  [Tool call: {$functionName}]\n";
        echo '  [Arguments: ' . json_encode($arguments) . "]\n";

        $result = match ($functionName) {
            'get_weather' => getWeather(
                $arguments['city'] ?? 'Unknown',
                $arguments['units'] ?? 'C',
            ),
            default => json_encode(['error' => "Unknown function: {$functionName}"]),
        };

        echo "  [Result: {$result}]\n\n";

        $results[] = ['id' => $callId, 'result' => $result];
    }

    return $results;
}

// Main chat loop
while (true) {
    echo 'You: ';
    $prompt = trim((string) fgets(STDIN));

    if (strtolower($prompt) === 'exit') {
        break;
    }

    if ($prompt === '') {
        continue;
    }

    $chat->append(user($prompt));

    try {
        if ($streaming) {
            // Streaming mode
            echo 'Grok: ';

            $lastResponse = null;

            foreach ($chat->stream() as [$response, $chunk]) {
                echo $chunk->content;
                $lastResponse = $response;
            }
            echo "\n";

            if ($lastResponse !== null) {
                $chat->append($lastResponse);

                // Check for tool calls
                $toolCalls = $lastResponse->getToolCalls();

                if (! empty($toolCalls)) {
                    echo "\n";
                    $results = processToolCalls($toolCalls);

                    // Add tool results to the conversation
                    foreach ($results as $result) {
                        $chat->append(toolResult($result['result'], $result['id']));
                    }

                    // Get the follow-up response
                    echo 'Grok: ';

                    foreach ($chat->stream() as [$response, $chunk]) {
                        echo $chunk->content;
                        $lastResponse = $response;
                    }
                    echo "\n";
                    $chat->append($lastResponse);
                }
            }
        } else {
            // Non-streaming mode
            $response = $chat->sample();
            $chat->append($response);

            if ($response->getContent()) {
                echo "Grok: {$response->getContent()}\n";
            }

            // Check for tool calls
            $toolCalls = $response->getToolCalls();

            if (! empty($toolCalls)) {
                echo "\n";
                $results = processToolCalls($toolCalls);

                // Add tool results to the conversation
                foreach ($results as $result) {
                    $chat->append(toolResult($result['result'], $result['id']));
                }

                // Get the follow-up response with the tool results
                $response = $chat->sample();
                echo "Grok: {$response->getContent()}\n";
                $chat->append($response);
            }
        }
    } catch (Displace\XaiSdk\Exceptions\XaiException $e) {
        echo "Error: {$e->getMessage()}\n";
    }

    echo "\n";
}

echo "Goodbye!\n";
