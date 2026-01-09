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

namespace Displace\XaiSdk\Exceptions;

use Throwable;

/**
 * Exception thrown when batch operations fail.
 *
 * This exception contains information about individual failures within a batch,
 * allowing callers to inspect which requests failed and why.
 */
class BatchException extends XaiException
{
    /** @var array<int, Throwable> */
    private array $failures = [];

    /**
     * Creates a BatchException with failure details.
     *
     * @param string $message The exception message.
     * @param array<int, Throwable> $failures Map of indices to their failures.
     *
     * @return self
     */
    public static function withFailures(string $message, array $failures): self
    {
        $exception = new self($message);
        $exception->failures = $failures;

        return $exception;
    }

    /**
     * Gets all failures that occurred.
     *
     * @return array<int, Throwable> Map of indices to their failures.
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * Gets the number of failures.
     */
    public function getFailureCount(): int
    {
        return count($this->failures);
    }

    /**
     * Gets a specific failure by index.
     *
     * @param int $index The request index.
     *
     * @return Throwable|null The failure, or null if that index succeeded.
     */
    public function getFailure(int $index): ?Throwable
    {
        return $this->failures[$index] ?? null;
    }

    /**
     * Gets the indices of failed requests.
     *
     * @return array<int> The indices.
     */
    public function getFailedIndices(): array
    {
        return array_keys($this->failures);
    }
}
