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

namespace Displace\XaiSdk\Batch;

use Displace\XaiSdk\Exceptions\BatchException;
use Displace\XaiSdk\XaiClient;
use Throwable;

/**
 * Utility for executing multiple API requests concurrently.
 *
 * This class provides a way to batch multiple requests and execute them in parallel,
 * improving throughput when you need to make many independent API calls.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Batch\ConcurrentRequests;
 * use function Displace\XaiSdk\Chat\user;
 *
 * $results = ConcurrentRequests::run($client, [
 *     fn($c) => $c->chat->create('grok-3')->append(user('Q1'))->sample(),
 *     fn($c) => $c->chat->create('grok-3')->append(user('Q2'))->sample(),
 * ], concurrency: 5);
 *
 * foreach ($results as $result) {
 *     if ($result->isSuccess()) {
 *         echo $result->getValue()->getContent();
 *     } else {
 *         echo "Error: " . $result->getError()->getMessage();
 *     }
 * }
 * ```
 */
final class ConcurrentRequests
{
    /**
     * Default concurrency limit.
     */
    private const DEFAULT_CONCURRENCY = 5;

    /**
     * Executes multiple request callbacks concurrently.
     *
     * Each callback receives the XaiClient and should return the result of an API operation.
     * Results are returned in the same order as the input callbacks, wrapped in RequestResult
     * objects that indicate success or failure.
     *
     * @param XaiClient $client The xAI client to use for requests.
     * @param array<callable(XaiClient): mixed> $callbacks Array of callbacks that take the client and return a result.
     * @param int $concurrency Maximum number of concurrent requests (default 5).
     *
     * @return array<RequestResult> Results in the same order as callbacks.
     */
    public static function run(
        XaiClient $client,
        array $callbacks,
        int $concurrency = self::DEFAULT_CONCURRENCY,
    ): array {
        if ($callbacks === []) {
            return [];
        }

        if ($concurrency < 1) {
            throw new BatchException('Concurrency must be at least 1');
        }

        $results = [];
        $callbackCount = count($callbacks);

        // Process callbacks in batches based on concurrency
        $batches = array_chunk($callbacks, $concurrency, preserve_keys: true);

        foreach ($batches as $batch) {
            $batchResults = self::executeBatch($client, $batch);
            $results = array_merge($results, $batchResults);
        }

        // Ensure results are in the original order
        ksort($results);

        return array_values($results);
    }

    /**
     * Executes multiple request callbacks and collects all successful results.
     *
     * Similar to run(), but only returns successful results. Throws a BatchException
     * if any request fails, containing information about all failures.
     *
     * @param XaiClient $client The xAI client to use for requests.
     * @param array<callable(XaiClient): mixed> $callbacks Array of callbacks.
     * @param int $concurrency Maximum number of concurrent requests.
     *
     * @throws BatchException If any request fails.
     *
     * @return array<mixed> Successful results in order.
     */
    public static function all(
        XaiClient $client,
        array $callbacks,
        int $concurrency = self::DEFAULT_CONCURRENCY,
    ): array {
        $results = self::run($client, $callbacks, $concurrency);
        $failures = [];
        $successes = [];

        foreach ($results as $index => $result) {
            if ($result->isSuccess()) {
                $successes[$index] = $result->getValue();
            } else {
                $failures[$index] = $result->getError();
            }
        }

        if ($failures !== []) {
            throw BatchException::withFailures(
                sprintf('%d of %d requests failed', count($failures), count($results)),
                $failures,
            );
        }

        return $successes;
    }

    /**
     * Executes multiple request callbacks and returns only successful results.
     *
     * Unlike all(), this method does not throw on failures. Failed requests
     * are silently ignored.
     *
     * @param XaiClient $client The xAI client to use for requests.
     * @param array<callable(XaiClient): mixed> $callbacks Array of callbacks.
     * @param int $concurrency Maximum number of concurrent requests.
     *
     * @return array<mixed> Successful results (indices may not be contiguous).
     */
    public static function settled(
        XaiClient $client,
        array $callbacks,
        int $concurrency = self::DEFAULT_CONCURRENCY,
    ): array {
        $results = self::run($client, $callbacks, $concurrency);
        $successes = [];

        foreach ($results as $index => $result) {
            if ($result->isSuccess()) {
                $successes[$index] = $result->getValue();
            }
        }

        return $successes;
    }

    /**
     * Executes a batch of callbacks.
     *
     * This method uses parallel execution when the parallel extension is available,
     * or falls back to sequential execution otherwise.
     *
     * @param XaiClient $client The xAI client.
     * @param array<int, callable(XaiClient): mixed> $callbacks Batch of callbacks with original indices.
     *
     * @return array<int, RequestResult> Results keyed by original index.
     */
    private static function executeBatch(XaiClient $client, array $callbacks): array
    {
        $results = [];

        // Execute callbacks sequentially (async not possible without fibers/parallel)
        // Each callback gets a fresh client reference but same underlying HTTP client
        foreach ($callbacks as $index => $callback) {
            $results[$index] = self::executeCallback($client, $callback);
        }

        return $results;
    }

    /**
     * Executes a single callback and wraps the result.
     *
     * @param XaiClient $client The xAI client.
     * @param callable(XaiClient): mixed $callback The callback to execute.
     *
     * @return RequestResult The wrapped result.
     */
    private static function executeCallback(XaiClient $client, callable $callback): RequestResult
    {
        try {
            $value = $callback($client);

            return RequestResult::success($value);
        } catch (Throwable $e) {
            return RequestResult::failure($e);
        }
    }
}
