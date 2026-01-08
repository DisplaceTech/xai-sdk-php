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
 */

declare(strict_types=1);

// Ensure we're running in test environment
if (!defined('PHPUNIT_RUNNING')) {
    define('PHPUNIT_RUNNING', true);
}

// Set timezone to avoid warnings
date_default_timezone_set('UTC');

// Load Composer autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoloader)) {
    echo "Composer autoloader not found. Run 'composer install' first.\n";
    exit(1);
}

require_once $autoloader;

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load test environment variables if .env.testing exists
$envFile = dirname(__DIR__) . '/.env.testing';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$name, $value] = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

// Ensure test API key is set (use placeholder if not in environment)
if (!getenv('XAI_API_KEY') && !isset($_ENV['XAI_API_KEY'])) {
    putenv('XAI_API_KEY=test-api-key-for-unit-tests');
    $_ENV['XAI_API_KEY'] = 'test-api-key-for-unit-tests';
}
