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

namespace Displace\XaiSdk\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case for xAI SDK tests.
 *
 * Provides common utilities and setup for all test classes.
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * Get a test API key.
     *
     * Returns the environment variable if set, otherwise returns a placeholder.
     */
    protected function getTestApiKey(): string
    {
        return getenv('XAI_API_KEY') ?: 'test-api-key';
    }

    /**
     * Get the path to a fixture file.
     */
    protected function getFixturePath(string $filename): string
    {
        return __DIR__ . '/Fixtures/' . $filename;
    }

    /**
     * Load a JSON fixture file.
     *
     * @return array<string, mixed>
     */
    protected function loadJsonFixture(string $filename): array
    {
        $path = $this->getFixturePath($filename);

        if (! file_exists($path)) {
            $this->fail("Fixture file not found: {$path}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            $this->fail("Could not read fixture file: {$path}");
        }

        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($data)) {
            $this->fail("Fixture file does not contain a valid JSON object: {$path}");
        }

        return $data;
    }

    /**
     * Assert that two arrays have the same keys (order independent).
     *
     * @param array<string, mixed> $expected
     * @param array<string, mixed> $actual
     */
    protected function assertArrayHasSameKeys(array $expected, array $actual, string $message = ''): void
    {
        $expectedKeys = array_keys($expected);
        $actualKeys = array_keys($actual);
        sort($expectedKeys);
        sort($actualKeys);

        $this->assertEquals($expectedKeys, $actualKeys, $message);
    }
}
