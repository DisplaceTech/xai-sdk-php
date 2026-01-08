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
 * Image Understanding Example
 * ===========================
 *
 * This example demonstrates how to use vision models to understand
 * and describe images. You can provide images as URLs or base64-encoded data.
 *
 * Usage:
 *   php examples/image_understanding.php              # Using URL
 *   php examples/image_understanding.php --base64     # Using base64
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\XaiClient;

use function Displace\XaiSdk\Chat\image;
use function Displace\XaiSdk\Chat\user;

// Parse command line arguments
$options = getopt('', ['base64', 'help']);
$useBase64 = isset($options['base64']);

if (isset($options['help'])) {
    echo <<<HELP
xAI PHP SDK - Image Understanding Example

Usage: php examples/image_understanding.php [OPTIONS]

Options:
  --base64    Use base64-encoded image data instead of URL
  --help      Show this help message

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

echo "xAI Image Understanding Example\n";
echo "================================\n\n";

/**
 * Image understanding with multiple images via URL.
 */
function imageUnderstandingUrl(XaiClient $client): void
{
    echo "Mode: URL-based images\n\n";

    $chat = $client->chat->create(model: 'grok-2-vision');

    // We can interleave text and images in a single user message
    $chat->append(
        user(
            'What do these images have in common?',
            image(
                'https://www.nasa.gov/wp-content/uploads/2025/04/51d-9092large.jpg',
                'high'
            ),
            image(
                'https://www.nasa.gov/wp-content/uploads/2025/04/0101247orig-1.jpg',
                'high'
            )
        )
    );

    echo "Analyzing two NASA images...\n\n";

    $response = $chat->sample();

    echo "Response:\n";
    echo $response->getContent();
    echo "\n\n";
    echo "Prompt tokens: {$response->usage->promptTokens}\n";
    echo "Completion tokens: {$response->usage->completionTokens}\n";
}

/**
 * Image understanding with base64-encoded image.
 */
function imageUnderstandingBase64(XaiClient $client): void
{
    echo "Mode: Base64-encoded image\n\n";

    // Download and encode an image
    $imageUrl = 'https://upload.wikimedia.org/wikipedia/commons/a/a7/Camponotus_flavomarginatus_ant.jpg';

    echo "Downloading image from Wikipedia...\n";

    $imageData = file_get_contents($imageUrl);
    if ($imageData === false) {
        echo "Error: Could not download image.\n";
        exit(1);
    }

    $base64Data = base64_encode($imageData);
    $dataUri = "data:image/jpeg;base64,{$base64Data}";

    echo "Image encoded (" . strlen($base64Data) . " bytes)\n\n";

    $chat = $client->chat->create(model: 'grok-2-vision');

    $chat->append(
        user(
            'What kind of ant is this? Provide details about this species.',
            image($dataUri)
        )
    );

    echo "Analyzing image...\n\n";

    $response = $chat->sample();

    echo "Response:\n";
    echo $response->getContent();
    echo "\n\n";
    echo "Prompt tokens: {$response->usage->promptTokens}\n";
    echo "Completion tokens: {$response->usage->completionTokens}\n";
}

// Run the appropriate mode
try {
    if ($useBase64) {
        imageUnderstandingBase64($client);
    } else {
        imageUnderstandingUrl($client);
    }
} catch (\Displace\XaiSdk\Exceptions\XaiException $e) {
    echo "\nError: {$e->getMessage()}\n";
    if ($e->getHttpStatusCode() !== null) {
        echo "HTTP Status: {$e->getHttpStatusCode()}\n";
    }
    exit(1);
}
