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

/**
 * Exception thrown when a deferred completion fails.
 *
 * This exception is raised when polling a deferred completion results in
 * an error, timeout, or cancellation.
 */
class DeferredCompletionException extends XaiException
{
    /**
     * Creates a new DeferredCompletionException instance.
     *
     * @param string $message The exception message.
     * @param string $deferredId The deferred completion ID.
     * @param string|null $reason The reason for the failure.
     */
    public function __construct(
        string $message,
        public readonly string $deferredId,
        public readonly ?string $reason = null,
    ) {
        parent::__construct($message);
    }

    /**
     * Creates an exception for a failed deferred completion.
     *
     * @param string $id The deferred completion ID.
     * @param string|null $error The error message.
     *
     * @return self
     */
    public static function failed(string $id, ?string $error = null): self
    {
        $message = sprintf('Deferred completion %s failed', $id);

        if ($error !== null) {
            $message .= ': ' . $error;
        }

        return new self($message, $id, $error);
    }

    /**
     * Creates an exception for a cancelled deferred completion.
     *
     * @param string $id The deferred completion ID.
     *
     * @return self
     */
    public static function cancelled(string $id): self
    {
        return new self(
            sprintf('Deferred completion %s was cancelled', $id),
            $id,
            'cancelled',
        );
    }

    /**
     * Creates an exception for a timeout while waiting for completion.
     *
     * @param string $id The deferred completion ID.
     * @param int $timeout The timeout in seconds.
     *
     * @return self
     */
    public static function timeout(string $id, int $timeout): self
    {
        return new self(
            sprintf('Timed out waiting for deferred completion %s after %d seconds', $id, $timeout),
            $id,
            'timeout',
        );
    }

    /**
     * Creates an exception when the result is missing from a completed deferred.
     *
     * @param string $id The deferred completion ID.
     *
     * @return self
     */
    public static function resultMissing(string $id): self
    {
        return new self(
            sprintf('Deferred completion %s completed but result is missing', $id),
            $id,
            'result_missing',
        );
    }
}
