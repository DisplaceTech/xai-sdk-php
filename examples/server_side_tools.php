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
 * Server-Side Tools Example
 * =========================
 *
 * This example demonstrates how to use server-side tools with the xAI API.
 * Server-side tools are executed by xAI's servers and include:
 * - Web Search: Search the web for current information
 * - X Search: Search X (Twitter) posts
 * - Code Execution: Run code on xAI's servers
 *
 * Usage:
 *   php examples/server_side_tools.php                    # Web search demo
 *   php examples/server_side_tools.php --code             # Code execution demo
 *   php examples/server_side_tools.php --x                # X search demo
 *   php examples/server_side_tools.php --combined         # Combined with client tools
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\Tools\ServerSideTools;
use Displace\XaiSdk\Tools\Tool;
use Displace\XaiSdk\XaiClient;

use function Displace\XaiSdk\Chat\toolResult;
use function Displace\XaiSdk\Chat\user;

// Parse command line arguments
$options = getopt('', ['code', 'x', 'combined', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
        xAI PHP SDK - Server-Side Tools Example

        Usage: php examples/server_side_tools.php [OPTIONS]

        Options:
          --code        Run code execution example
          --x           Run X (Twitter) search example
          --combined    Run combined server + client tools example
          --help        Show this help message

        Environment:
          XAI_API_KEY   Your xAI API key (required)

        Without options, runs the web search example.

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

echo "xAI Server-Side Tools Example\n";
echo "=============================\n\n";

/**
 * Demonstrates web search tool usage.
 */
function webSearchExample(XaiClient $client): void
{
    echo "=== Web Search Example ===\n\n";

    $chat = $client->chat->create(
        model: 'grok-3',
        tools: [
            ServerSideTools::webSearch(),
        ],
    );

    $query = "What was the result of Arsenal's most recent game? Who scored?";

    echo "Query: {$query}\n\n";
    echo "Sending request with web search enabled...\n\n";

    $chat->append(user($query));

    $isThinking = true;

    foreach ($chat->stream() as [$response, $chunk]) {
        // Display tool calls as they happen
        $toolCalls = $chunk->toolCalls ?? [];

        foreach ($toolCalls as $toolCall) {
            $name = $toolCall['function']['name'] ?? 'unknown';
            $args = $toolCall['function']['arguments'] ?? '{}';
            echo "\n[Tool call: {$name}]\n";
            echo "[Arguments: {$args}]\n\n";
        }

        // Show thinking progress
        if ($response->usage->reasoningTokens > 0 && $isThinking) {
            echo "\rThinking... ({$response->usage->reasoningTokens} tokens)";
        }

        // Show final content
        if ($chunk->content !== '' && $isThinking) {
            echo "\n\nFinal Response:\n";
            $isThinking = false;
        }

        if ($chunk->content !== '' && ! $isThinking) {
            echo $chunk->content;
        }
    }

    echo "\n\n---\n";
    echo "Usage:\n";
    echo "  Prompt tokens: {$response->usage->promptTokens}\n";
    echo "  Completion tokens: {$response->usage->completionTokens}\n";
    echo "  Total tokens: {$response->usage->totalTokens}\n";

    if (! empty($response->citations)) {
        echo "\nCitations:\n";

        foreach ($response->citations as $citation) {
            echo "  - {$citation}\n";
        }
    }
}

/**
 * Demonstrates code execution tool usage.
 */
function codeExecutionExample(XaiClient $client): void
{
    echo "=== Code Execution Example ===\n\n";

    $chat = $client->chat->create(
        model: 'grok-3',
        tools: [
            ServerSideTools::codeExecution(),
        ],
    );

    $query = 'What is the 50th number in the Fibonacci sequence? Please calculate it and show me the code.';

    echo "Query: {$query}\n\n";
    echo "Sending request with code execution enabled...\n\n";

    $chat->append(user($query));

    $isThinking = true;

    foreach ($chat->stream() as [$response, $chunk]) {
        // Display tool calls
        $toolCalls = $chunk->toolCalls ?? [];

        foreach ($toolCalls as $toolCall) {
            $name = $toolCall['function']['name'] ?? 'unknown';
            echo "\n[Executing code...]\n";
        }

        // Display tool outputs (code execution results)
        $toolOutputs = $chunk->toolOutputs ?? [];

        foreach ($toolOutputs as $output) {
            if (isset($output['content'])) {
                echo "[Code output: {$output['content']}]\n\n";
            }
        }

        // Show thinking progress
        if ($response->usage->reasoningTokens > 0 && $isThinking) {
            echo "\rThinking... ({$response->usage->reasoningTokens} tokens)";
        }

        // Show final content
        if ($chunk->content !== '' && $isThinking) {
            echo "\n\nFinal Response:\n";
            $isThinking = false;
        }

        if ($chunk->content !== '' && ! $isThinking) {
            echo $chunk->content;
        }
    }

    echo "\n\n---\n";
    echo "Usage:\n";
    echo "  Prompt tokens: {$response->usage->promptTokens}\n";
    echo "  Completion tokens: {$response->usage->completionTokens}\n";
    echo "  Total tokens: {$response->usage->totalTokens}\n";
}

/**
 * Demonstrates X (Twitter) search tool usage.
 */
function xSearchExample(XaiClient $client): void
{
    echo "=== X (Twitter) Search Example ===\n\n";

    $chat = $client->chat->create(
        model: 'grok-3',
        tools: [
            ServerSideTools::xSearch(
                fromDate: new DateTimeImmutable('-7 days'),
                toDate: new DateTimeImmutable('now'),
            ),
        ],
    );

    $query = 'What are people saying on X about the latest AI developments?';

    echo "Query: {$query}\n\n";
    echo "Sending request with X search enabled...\n\n";

    $chat->append(user($query));

    $isThinking = true;

    foreach ($chat->stream() as [$response, $chunk]) {
        // Display tool calls
        $toolCalls = $chunk->toolCalls ?? [];

        foreach ($toolCalls as $toolCall) {
            $name = $toolCall['function']['name'] ?? 'unknown';
            $args = $toolCall['function']['arguments'] ?? '{}';
            echo "\n[Tool call: {$name}]\n";
            echo "[Arguments: {$args}]\n\n";
        }

        // Show thinking progress
        if ($response->usage->reasoningTokens > 0 && $isThinking) {
            echo "\rThinking... ({$response->usage->reasoningTokens} tokens)";
        }

        // Show final content
        if ($chunk->content !== '' && $isThinking) {
            echo "\n\nFinal Response:\n";
            $isThinking = false;
        }

        if ($chunk->content !== '' && ! $isThinking) {
            echo $chunk->content;
        }
    }

    echo "\n\n---\n";
    echo "Usage:\n";
    echo "  Prompt tokens: {$response->usage->promptTokens}\n";
    echo "  Completion tokens: {$response->usage->completionTokens}\n";
    echo "  Total tokens: {$response->usage->totalTokens}\n";
}

/**
 * Demonstrates combining server-side and client-side tools.
 */
function combinedToolsExample(XaiClient $client): void
{
    echo "=== Combined Server + Client Tools Example ===\n\n";

    // Define a client-side weather tool
    $weatherTool = new Tool(
        name: 'get_weather',
        description: 'Get the current weather for a given city.',
        parameters: [
            'type' => 'object',
            'properties' => [
                'city' => [
                    'type' => 'string',
                    'description' => 'The name of the city',
                ],
            ],
            'required' => ['city'],
        ],
    );

    // Simulated weather function
    $getWeather = function (string $city): string {
        return json_encode([
            'city' => $city,
            'temperature' => 72,
            'units' => 'F',
            'conditions' => 'sunny',
            'description' => "The weather in {$city} is sunny at 72°F.",
        ]);
    };

    $chat = $client->chat->create(
        model: 'grok-3',
        tools: [
            ServerSideTools::webSearch(),
            $weatherTool,
        ],
    );

    $query = "What team won the 2024 Super Bowl, and what's the current weather in their home city?";

    echo "Query: {$query}\n\n";
    echo "This query will use:\n";
    echo "  - Web search (server-side) to find the Super Bowl winner\n";
    echo "  - Weather tool (client-side) to get the weather\n\n";
    echo "Sending request...\n\n";

    $chat->append(user($query));

    while (true) {
        $clientSideToolCalls = [];

        foreach ($chat->stream() as [$response, $chunk]) {
            // Check for tool calls
            $toolCalls = $chunk->toolCalls ?? [];

            foreach ($toolCalls as $toolCall) {
                $name = $toolCall['function']['name'] ?? '';

                // Determine if client-side or server-side
                if ($name === 'get_weather') {
                    $clientSideToolCalls[] = $toolCall;
                    echo "[Client-side tool call: {$name}]\n";
                    echo '[Arguments: ' . ($toolCall['function']['arguments'] ?? '{}') . "]\n\n";
                } else {
                    echo "[Server-side tool call: {$name}]\n";
                    echo '[Arguments: ' . ($toolCall['function']['arguments'] ?? '{}') . "]\n\n";
                }
            }

            // Show content as it streams
            if ($chunk->content !== '') {
                echo $chunk->content;
            }
        }

        // Append response to conversation
        $chat->append($response);

        // If no client-side tool calls, we're done
        if (empty($clientSideToolCalls)) {
            break;
        }

        // Process client-side tool calls
        foreach ($clientSideToolCalls as $toolCall) {
            $args = json_decode($toolCall['function']['arguments'] ?? '{}', true);
            $city = $args['city'] ?? 'Unknown';

            echo "[Executing client-side tool for city: {$city}]\n";
            $result = $getWeather($city);
            echo "[Result: {$result}]\n\n";

            $chat->append(toolResult($result, $toolCall['id'] ?? ''));
        }

        echo "Continuing conversation with tool results...\n\n";
    }

    echo "\n---\n";
    echo "Usage:\n";
    echo "  Prompt tokens: {$response->usage->promptTokens}\n";
    echo "  Completion tokens: {$response->usage->completionTokens}\n";
    echo "  Total tokens: {$response->usage->totalTokens}\n";
}

// Run the appropriate example
try {
    if (isset($options['code'])) {
        codeExecutionExample($client);
    } elseif (isset($options['x'])) {
        xSearchExample($client);
    } elseif (isset($options['combined'])) {
        combinedToolsExample($client);
    } else {
        webSearchExample($client);
    }
} catch (Displace\XaiSdk\Exceptions\XaiException $e) {
    echo "\nError: {$e->getMessage()}\n";

    if ($e->getHttpStatusCode() !== null) {
        echo "HTTP Status: {$e->getHttpStatusCode()}\n";
    }
    exit(1);
}
