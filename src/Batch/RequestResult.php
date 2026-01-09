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

use LogicException;
use Throwable;

/**
 * Represents the result of a concurrent request.
 *
 * This class wraps either a successful value or an error, allowing callers
 * to safely handle both cases.
 *
 * @template T
 */
final readonly class RequestResult
{
    /**
     * @param T|null $value The successful value, if any.
     * @param Throwable|null $error The error, if any.
     * @param bool $success Whether the request was successful.
     */
    private function __construct(
        private mixed $value,
        private ?Throwable $error,
        private bool $success,
    ) {
    }

    /**
     * Creates a successful result.
     *
     * @template TValue
     *
     * @param TValue $value The successful value.
     *
     * @return self<TValue>
     */
    public static function success(mixed $value): self
    {
        return new self($value, null, true);
    }

    /**
     * Creates a failure result.
     *
     * @param Throwable $error The error that occurred.
     *
     * @return self<null>
     */
    public static function failure(Throwable $error): self
    {
        return new self(null, $error, false);
    }

    /**
     * Returns whether this result represents a success.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Returns whether this result represents a failure.
     */
    public function isFailure(): bool
    {
        return ! $this->success;
    }

    /**
     * Gets the successful value.
     *
     * @throws LogicException If this is a failure result.
     *
     * @return T The value.
     */
    public function getValue(): mixed
    {
        if (! $this->success) {
            throw new LogicException(
                'Cannot get value from a failure result. Check isSuccess() first.',
            );
        }

        return $this->value;
    }

    /**
     * Gets the error from a failed result.
     *
     * @throws LogicException If this is a success result.
     */
    public function getError(): Throwable
    {
        if ($this->success) {
            throw new LogicException(
                'Cannot get error from a success result. Check isFailure() first.',
            );
        }

        /** @var Throwable */
        return $this->error;
    }

    /**
     * Gets the value or returns a default if this is a failure.
     *
     * @template TDefault
     *
     * @param TDefault $default The default value.
     *
     * @return T|TDefault
     */
    public function getValueOrDefault(mixed $default): mixed
    {
        return $this->success ? $this->value : $default;
    }

    /**
     * Gets the value or calls a callback with the error if this is a failure.
     *
     * @template TRecovered
     *
     * @param callable(Throwable): TRecovered $callback The recovery callback.
     *
     * @return T|TRecovered
     */
    public function getValueOrElse(callable $callback): mixed
    {
        if ($this->success) {
            return $this->value;
        }

        /** @var Throwable $error */
        $error = $this->error;

        return $callback($error);
    }

    /**
     * Transforms the value if successful.
     *
     * @template TNew
     *
     * @param callable(T): TNew $mapper The transformation function.
     *
     * @return self<TNew>
     */
    public function map(callable $mapper): self
    {
        if (! $this->success) {
            /** @var self<TNew> */
            return new self(null, $this->error, false);
        }

        return self::success($mapper($this->value));
    }

    /**
     * Transforms the value if successful, allowing the mapper to return a new Result.
     *
     * @template TNew
     *
     * @param callable(T): self<TNew> $mapper The transformation function.
     *
     * @return self<TNew>
     */
    public function flatMap(callable $mapper): self
    {
        if (! $this->success) {
            /** @var self<TNew> */
            return new self(null, $this->error, false);
        }

        return $mapper($this->value);
    }

    /**
     * Throws the error if this is a failure, otherwise returns the value.
     *
     * @throws Throwable If this is a failure result.
     *
     * @return T The value.
     */
    public function getOrThrow(): mixed
    {
        if (! $this->success) {
            throw $this->error;
        }

        return $this->value;
    }
}
