<?php

/**
 * This file is part of the xAI PHP SDK.
 *
 * (c) 2026 Displace Technologies, LLC
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * Example Test Runner
 * ===================
 *
 * This script runs all SDK examples to verify they work correctly
 * against the live xAI API.
 *
 * Usage:
 *   XAI_API_KEY=your-key php tests/run-examples.php
 *   XAI_API_KEY=your-key composer test:examples
 *
 * Options:
 *   --verbose    Show full output from each example
 *   --stop       Stop on first failure
 *   --only=name  Run only the specified example (e.g., --only=chat)
 */

declare(strict_types=1);

// Check for API key
if (getenv('XAI_API_KEY') === false) {
    echo "\033[31mError: XAI_API_KEY environment variable is required.\033[0m\n";
    echo "Usage: XAI_API_KEY=your-key php tests/run-examples.php\n";
    exit(1);
}

// Parse options
$options = getopt('', ['verbose', 'stop', 'only:']);
$verbose = isset($options['verbose']);
$stopOnFailure = isset($options['stop']);
$onlyExample = isset($options['only']) && is_string($options['only']) ? $options['only'] : null;

// Define examples and their configurations
$examples = [
    'chat' => [
        'file' => 'chat.php',
        'description' => 'Basic chat completions',
        'timeout' => 60,
        'args' => '--test',
    ],
    'streaming' => [
        'file' => 'streaming.php',
        'description' => 'Streaming chat responses',
        'timeout' => 30,
    ],
    'function_calling' => [
        'file' => 'function_calling.php',
        'description' => 'Function/tool calling',
        'timeout' => 60,
        'args' => '--test',
    ],
    'structured_outputs' => [
        'file' => 'structured_outputs.php',
        'description' => 'JSON structured outputs',
        'timeout' => 60,
    ],
    'reasoning' => [
        'file' => 'reasoning.php',
        'description' => 'Chain-of-thought reasoning',
        'timeout' => 120,
        'args' => '--test',
    ],
    'image_understanding' => [
        'file' => 'image_understanding.php',
        'description' => 'Vision/image analysis',
        'timeout' => 60,
    ],
    'image_generation' => [
        'file' => 'image_generation.php',
        'description' => 'Image generation (Aurora)',
        'timeout' => 180,
        'args' => '--test',
    ],
    'search' => [
        'file' => 'search.php',
        'description' => 'Live search grounding',
        'timeout' => 120,
        'expected_failure' => 'Requires grok-4 model (may need special access)',
    ],
    'server_side_tools' => [
        'file' => 'server_side_tools.php',
        'description' => 'Server-side tool execution',
        'timeout' => 120,
        'expected_failure' => 'Requires grok-4 model and responses endpoint (may need special access)',
    ],
    'collections' => [
        'file' => 'collections.php',
        'description' => 'Collections API (RAG)',
        'timeout' => 120,
        'expected_failure' => 'Collections API not yet publicly available',
    ],
    'telemetry' => [
        'file' => 'telemetry.php',
        'description' => 'OpenTelemetry integration',
        'timeout' => 30,
        'skip' => 'Requires OpenTelemetry SDK setup',
    ],
    'embeddings' => [
        'file' => 'embeddings.php',
        'description' => 'Embeddings API',
        'timeout' => 30,
        'expected_failure' => 'Embeddings API not yet publicly available',
    ],
];

// Filter to single example if requested
if ($onlyExample !== null && $onlyExample !== '') {
    if (! isset($examples[$onlyExample])) {
        echo "\033[31mError: Unknown example '{$onlyExample}'.\033[0m\n";
        echo 'Available examples: ' . implode(', ', array_keys($examples)) . "\n";
        exit(1);
    }
    $examples = [$onlyExample => $examples[$onlyExample]];
}

$examplesDir = dirname(__DIR__) . '/examples';
$passed = 0;
$failed = 0;
$skipped = 0;
$expectedFailures = 0;
$results = [];

echo "\n";
echo "\033[1m=== xAI PHP SDK Example Tests ===\033[0m\n";
echo 'Running ' . count($examples) . " example(s)...\n\n";

foreach ($examples as $name => $config) {
    $file = $examplesDir . '/' . $config['file'];
    $description = $config['description'];
    $timeout = $config['timeout'];
    $args = $config['args'] ?? '';

    echo sprintf('  %-25s %s ', $name, str_pad($description, 35, '.'));

    // Check for skip condition
    if (isset($config['skip'])) {
        echo "\033[33mSKIPPED\033[0m ({$config['skip']})\n";
        $skipped++;
        $results[$name] = ['status' => 'skipped', 'reason' => $config['skip']];
        continue;
    }

    // Check file exists
    if (! file_exists($file)) {
        echo "\033[31mMISSING\033[0m\n";
        $failed++;
        $results[$name] = ['status' => 'missing'];
        continue;
    }

    // Run the example
    $command = sprintf(
        'timeout %d php %s %s 2>&1',
        $timeout,
        escapeshellarg($file),
        $args,
    );

    $startTime = microtime(true);
    $output = [];
    $exitCode = 0;
    exec($command, $output, $exitCode);
    $duration = round(microtime(true) - $startTime, 2);
    $outputStr = implode("\n", $output);

    // Check results
    if ($exitCode === 0) {
        echo "\033[32mPASSED\033[0m ({$duration}s)\n";
        $passed++;
        $results[$name] = ['status' => 'passed', 'duration' => $duration];
    } elseif (isset($config['expected_failure'])) {
        echo "\033[33mEXPECTED FAIL\033[0m ({$config['expected_failure']})\n";
        $expectedFailures++;
        $results[$name] = [
            'status' => 'expected_failure',
            'reason' => $config['expected_failure'],
            'output' => $outputStr,
        ];
    } else {
        echo "\033[31mFAILED\033[0m (exit code: {$exitCode})\n";
        $failed++;
        $results[$name] = [
            'status' => 'failed',
            'exit_code' => $exitCode,
            'output' => $outputStr,
            'duration' => $duration,
        ];

        if ($verbose) {
            echo "\n    \033[90mOutput:\033[0m\n";

            foreach ($output as $line) {
                echo '    ' . $line . "\n";
            }
            echo "\n";
        }

        if ($stopOnFailure) {
            break;
        }
    }
}

// Summary
echo "\n";
echo "\033[1m=== Summary ===\033[0m\n";
echo sprintf("  Passed:           \033[32m%d\033[0m\n", $passed);
echo sprintf("  Failed:           \033[31m%d\033[0m\n", $failed);
echo sprintf("  Skipped:          \033[33m%d\033[0m\n", $skipped);
echo sprintf("  Expected Failures: \033[33m%d\033[0m\n", $expectedFailures);
echo "\n";

// Show failed example details
if ($failed > 0 && ! $verbose) {
    echo "\033[1mFailed Examples:\033[0m\n";

    foreach ($results as $name => $result) {
        if ($result['status'] === 'failed') {
            echo "\n  \033[31m{$name}\033[0m (exit code: {$result['exit_code']})\n";
            $outputLines = explode("\n", $result['output']);
            // Show last 10 lines of output
            $relevantLines = array_slice($outputLines, -10);

            foreach ($relevantLines as $line) {
                echo '    ' . $line . "\n";
            }
        }
    }
    echo "\n";
}

// Exit with appropriate code
if ($failed > 0) {
    exit(1);
}
exit(0);
