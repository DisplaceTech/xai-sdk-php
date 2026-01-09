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
 * Image Generation Example
 * ========================
 *
 * This example demonstrates how to generate images using the xAI API.
 * You can generate single images or batches, with output as URL or base64.
 *
 * Usage:
 *   php examples/image_generation.php --output-dir=./images
 *   php examples/image_generation.php --output-dir=./images --n=4
 *   php examples/image_generation.php --output-dir=./images --format=url
 *
 * Requirements:
 *   - Set the XAI_API_KEY environment variable
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Displace\XaiSdk\XaiClient;

// Parse command line arguments
$options = getopt('', ['output-dir:', 'n::', 'format::', 'help', 'test']);
$testMode = isset($options['test']);

if (isset($options['help'])) {
    echo <<<HELP
        xAI PHP SDK - Image Generation Example

        Usage: php examples/image_generation.php --output-dir=<dir> [OPTIONS]

        Options:
          --output-dir=<dir>  Directory to save generated images (required unless --test)
          --n=<count>         Number of images to generate (default: 1)
          --format=<format>   Image format: 'base64' or 'url' (default: base64)
          --test              Run a single test generation (non-interactive, uses temp dir)
          --help              Show this help message

        Environment:
          XAI_API_KEY   Your xAI API key (required)

        Examples:
          "A sunset over mountains"
          "A futuristic cityscape"
          "A cat wearing a top hat"

        HELP;
    exit(0);
}

// Validate output directory
$outputDir = $options['output-dir'] ?? null;

// In test mode, use a temp directory if not specified
if ($testMode && $outputDir === null) {
    $outputDir = sys_get_temp_dir() . '/xai-sdk-test-images-' . uniqid();
}

if ($outputDir === null) {
    echo "Error: --output-dir is required.\n";
    echo "Usage: php examples/image_generation.php --output-dir=./images\n";
    exit(1);
}

if (! is_dir($outputDir)) {
    if (! mkdir($outputDir, 0o755, true)) {
        echo "Error: Could not create output directory: {$outputDir}\n";
        exit(1);
    }
}

$n = (int) ($options['n'] ?? 1);
$format = $options['format'] ?? 'base64';

if (! in_array($format, ['base64', 'url'], true)) {
    echo "Error: Invalid format. Use 'base64' or 'url'.\n";
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

echo "xAI Image Generation Example\n";
echo "============================\n";
echo "Output directory: {$outputDir}\n";
echo "Images per prompt: {$n}\n";
echo "Format: {$format}\n";

// Test mode: generate a single test image and exit
if ($testMode) {
    echo "Mode: test\n\n";

    try {
        $prompt = 'A simple blue circle on a white background';
        echo "Test prompt: {$prompt}\n";
        echo "Generating test image...\n";

        $image = $client->image->sample($prompt, 'grok-2-image-1212', 'base64');
        $filename = "{$outputDir}/test_image.jpg";
        $bytes = $image->saveToFile($filename);

        echo "  Saved: {$filename} ({$bytes} bytes)\n";

        // Cleanup
        if (file_exists($filename)) {
            unlink($filename);
            rmdir($outputDir);
        }

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

$turn = 0;

while (true) {
    echo 'Prompt: ';
    $prompt = trim((string) fgets(STDIN));

    if (strtolower($prompt) === 'exit') {
        break;
    }

    if ($prompt === '') {
        continue;
    }

    echo "Generating {$n} image(s)...\n";

    try {
        if ($n === 1) {
            // Generate single image
            $image = $client->image->sample($prompt, 'grok-2-image-1212', $format);
            $images = [$image];
        } else {
            // Generate batch
            $images = $client->image->sampleBatch($prompt, $n, 'grok-2-image-1212', $format);
        }

        // Save images
        foreach ($images as $i => $image) {
            $filename = "{$outputDir}/image_{$turn}_{$i}.jpg";
            $bytes = $image->saveToFile($filename);

            echo "  Saved: {$filename} ({$bytes} bytes)\n";

            if ($image->revisedPrompt !== '' && $image->revisedPrompt !== $prompt) {
                echo "  Revised prompt: {$image->revisedPrompt}\n";
            }
        }

        echo 'Generated ' . count($images) . " image(s) successfully.\n";
    } catch (Displace\XaiSdk\Exceptions\XaiException $e) {
        echo "Error: {$e->getMessage()}\n";

        if ($e->getHttpStatusCode() !== null) {
            echo "HTTP Status: {$e->getHttpStatusCode()}\n";
        }
    }

    $turn++;
    echo "\n";
}

echo "Goodbye!\n";
