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
 * Structured Outputs Example
 * ==========================
 *
 * This example demonstrates how to get structured (JSON) outputs from the xAI API.
 * You can define a JSON schema for the expected response format, and the model
 * will return data matching that schema.
 *
 * Usage:
 *   php examples/structured_outputs.php
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\XaiClient;

use function Displace\XaiSdk\Chat\image;
use function Displace\XaiSdk\Chat\system;
use function Displace\XaiSdk\Chat\user;

// Parse command line arguments
$options = getopt('', ['help']);

if (isset($options['help'])) {
    echo <<<HELP
xAI PHP SDK - Structured Outputs Example

Usage: php examples/structured_outputs.php

This example demonstrates extracting structured data from an image of a receipt.
The model will return JSON matching a defined schema.

Environment:
  XAI_API_KEY   Your xAI API key (required)

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

echo "xAI Structured Outputs Example\n";
echo "==============================\n\n";

// Define the JSON schema for the receipt
$receiptSchema = [
    'type' => 'json_schema',
    'json_schema' => [
        'name' => 'receipt',
        'strict' => true,
        'schema' => [
            'type' => 'object',
            'properties' => [
                'date' => [
                    'type' => 'string',
                    'description' => 'The date of the receipt in ISO 8601 format',
                ],
                'items' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => 'Name of the item',
                            ],
                            'quantity' => [
                                'type' => 'integer',
                                'description' => 'Quantity purchased',
                            ],
                            'price_in_cents' => [
                                'type' => 'integer',
                                'description' => 'Price in cents',
                            ],
                        ],
                        'required' => ['name', 'quantity', 'price_in_cents'],
                        'additionalProperties' => false,
                    ],
                ],
                'currency' => [
                    'type' => 'string',
                    'description' => 'Currency code (e.g., USD, EUR)',
                ],
                'total_in_cents' => [
                    'type' => 'integer',
                    'description' => 'Total amount in cents',
                ],
            ],
            'required' => ['date', 'items', 'currency', 'total_in_cents'],
            'additionalProperties' => false,
        ],
    ],
];

// Create chat with vision model and structured output format
$chat = $client->chat->create(
    model: 'grok-2-vision',
    messages: [
        system('You are an expert at extracting information from receipts. You pay great attention to detail. Always respond with valid JSON matching the provided schema.'),
    ],
    responseFormat: $receiptSchema
);

// Sample receipt image
$receiptImageUrl = 'https://images.pexels.com/photos/13431759/pexels-photo-13431759.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2';

echo "Analyzing receipt image...\n\n";

// Add the user message with the image
$chat->append(
    user(
        'Extract the information contained in this receipt.',
        image($receiptImageUrl, 'high')
    )
);

try {
    $response = $chat->sample();
    $content = $response->getContent();

    echo "Raw Response:\n";
    echo "{$content}\n\n";

    // Parse the JSON response
    $receipt = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

    echo "Parsed Receipt:\n";
    echo "---------------\n";
    echo "Date: {$receipt['date']}\n\n";

    echo "Items:\n";
    foreach ($receipt['items'] as $item) {
        $price = number_format($item['price_in_cents'] / 100, 2);
        echo "  {$item['quantity']}x {$item['name']} - {$price} {$receipt['currency']}\n";
    }

    $total = number_format($receipt['total_in_cents'] / 100, 2);
    echo "\nTotal: {$total} {$receipt['currency']}\n";

    echo "\n---------------\n";
    echo "Usage:\n";
    echo "  Prompt tokens: {$response->usage->promptTokens}\n";
    echo "  Completion tokens: {$response->usage->completionTokens}\n";
    echo "  Total tokens: {$response->usage->totalTokens}\n";
} catch (\JsonException $e) {
    echo "Error parsing JSON response: {$e->getMessage()}\n";
    exit(1);
} catch (\Displace\XaiSdk\Exceptions\XaiException $e) {
    echo "\nError: {$e->getMessage()}\n";
    if ($e->getHttpStatusCode() !== null) {
        echo "HTTP Status: {$e->getHttpStatusCode()}\n";
    }
    exit(1);
}
