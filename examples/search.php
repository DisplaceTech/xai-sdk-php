<?php

/**
 * Search Example - Using X Search and Web Search with xAI API.
 *
 * This example demonstrates how to use server-side search tools
 * (x_search and web_search) with the /v1/responses endpoint.
 *
 * Server-side tools require the responses endpoint, not chat completions.
 *
 * Usage:
 *   export XAI_API_KEY="your-api-key"
 *   php examples/search.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Displace\XaiSdk\Tools\ServerSideTools;
use Displace\XaiSdk\XaiClient;

use function Displace\XaiSdk\Chat\system;
use function Displace\XaiSdk\Chat\user;

// Create the client
$client = new XaiClient();

echo "=== X Search Example ===\n\n";

// Example 1: Search X (Twitter) for recent posts
$xSearchResponse = $client->responses->create(
    model: 'grok-3-fast',
    messages: [
        system('You are a helpful assistant with access to X (Twitter) search.'),
        user('What are people saying about PHP 8.4 on X today?'),
    ],
    tools: [
        ServerSideTools::xSearch(
            fromDate: new DateTimeImmutable('-7 days'),
        ),
    ],
);

echo "X Search Response:\n";
echo $xSearchResponse->getContent() . "\n\n";

// Show tool calls if any
$toolCalls = $xSearchResponse->getToolCalls();

if (count($toolCalls) > 0) {
    echo "Tool calls made:\n";

    foreach ($toolCalls as $toolCall) {
        echo "  - {$toolCall->name} (status: {$toolCall->toolStatus})\n";
    }
    echo "\n";
}

echo "=== Web Search Example ===\n\n";

// Example 2: Search the web
$webSearchResponse = $client->responses->create(
    model: 'grok-3-fast',
    messages: [
        system('You are a helpful assistant with access to web search.'),
        user('What are the latest features in Laravel 11?'),
    ],
    tools: [
        ServerSideTools::webSearch(),
    ],
);

echo "Web Search Response:\n";
echo $webSearchResponse->getContent() . "\n\n";

echo "=== Combined Search Example ===\n\n";

// Example 3: Using both X search and web search
$combinedResponse = $client->responses->create(
    model: 'grok-3-fast',
    messages: [
        system('You are a research assistant with access to both X and web search. Cross-reference information from both sources.'),
        user('What is the community reaction to the new OpenAI o3 model? Check both X posts and tech news sites.'),
    ],
    tools: [
        ServerSideTools::xSearch(fromDate: new DateTimeImmutable('-3 days')),
        ServerSideTools::webSearch(),
    ],
);

echo "Combined Search Response:\n";
echo $combinedResponse->getContent() . "\n\n";

// Show usage statistics
if ($combinedResponse->usage !== null) {
    echo "Token Usage:\n";
    echo "  Input tokens: {$combinedResponse->usage->promptTokens}\n";
    echo "  Output tokens: {$combinedResponse->usage->completionTokens}\n";
    echo "  Total tokens: {$combinedResponse->usage->totalTokens}\n";
}

echo "\nDone!\n";
