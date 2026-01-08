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

use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanContextInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Context\ContextInterface;

/**
 * A no-operation span builder that creates no-op spans.
 *
 * Used when tracing is disabled.
 */
class NoOpSpanBuilder implements SpanBuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function setParent(ContextInterface|false|null $context): SpanBuilderInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setNoParent(): SpanBuilderInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addLink(SpanContextInterface $context, iterable $attributes = []): SpanBuilderInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute(string $key, mixed $value): SpanBuilderInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setAttributes(iterable $attributes): SpanBuilderInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setStartTimestamp(int $timestampNanos): SpanBuilderInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setSpanKind(int $spanKind): SpanBuilderInterface
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function startSpan(): SpanInterface
    {
        return new NoOpSpan();
    }
}
