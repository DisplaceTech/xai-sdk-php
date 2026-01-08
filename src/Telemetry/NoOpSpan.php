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

namespace Displace\XaiSdk\Telemetry;

use OpenTelemetry\API\Trace\SpanContextInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\ScopeInterface;
use Throwable;

/**
 * A no-operation span that does nothing.
 *
 * Used when tracing is disabled.
 */
class NoOpSpan implements SpanInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getCurrent(): SpanInterface
    {
        return new self();
    }

    /**
     * {@inheritDoc}
     */
    public static function fromContext(ContextInterface $context): SpanInterface
    {
        return new self();
    }

    /**
     * {@inheritDoc}
     */
    public static function getInvalid(): SpanInterface
    {
        return new self();
    }

    /**
     * {@inheritDoc}
     */
    public static function wrap(SpanContextInterface $spanContext): SpanInterface
    {
        return new self();
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute(string $key): mixed
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getContext(): SpanContextInterface
    {
        return new NoOpSpanContext();
    }

    /**
     * {@inheritDoc}
     */
    public function isRecording(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute(string $key, mixed $value): SpanInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setAttributes(iterable $attributes): SpanInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addEvent(string $name, iterable $attributes = [], ?int $timestamp = null): SpanInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addLink(SpanContextInterface $context, iterable $attributes = []): SpanInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function recordException(Throwable $exception, iterable $attributes = []): SpanInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function updateName(string $name): SpanInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setStatus(string $code, ?string $description = null): SpanInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function end(?int $endEpochNanos = null): void
    {
        // No-op
    }

    /**
     * {@inheritDoc}
     */
    public function storeInContext(ContextInterface $context): ContextInterface
    {
        return $context;
    }

    /**
     * {@inheritDoc}
     */
    public function activate(): ScopeInterface
    {
        return new NoOpScope();
    }
}
