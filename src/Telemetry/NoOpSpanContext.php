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
use OpenTelemetry\API\Trace\TraceStateInterface;

/**
 * A no-operation span context.
 *
 * Used when tracing is disabled.
 */
class NoOpSpanContext implements SpanContextInterface
{
    /**
     * {@inheritDoc}
     */
    public function getTraceId(): string
    {
        return str_repeat('0', 32);
    }

    /**
     * {@inheritDoc}
     */
    public function getSpanId(): string
    {
        return str_repeat('0', 16);
    }

    /**
     * {@inheritDoc}
     */
    public function getTraceFlags(): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getTraceState(): ?TraceStateInterface
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function isValid(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isRemote(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isSampled(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public static function createFromRemoteParent(
        string $traceId,
        string $spanId,
        int $traceFlags = 0,
        ?TraceStateInterface $traceState = null,
    ): SpanContextInterface {
        return new self();
    }

    /**
     * {@inheritDoc}
     */
    public static function getInvalid(): SpanContextInterface
    {
        return new self();
    }

    /**
     * {@inheritDoc}
     */
    public static function create(
        string $traceId,
        string $spanId,
        int $traceFlags = 0,
        ?TraceStateInterface $traceState = null,
    ): SpanContextInterface {
        return new self();
    }

    /**
     * {@inheritDoc}
     */
    public function getTraceIdBinary(): string
    {
        return str_repeat("\0", 16);
    }

    /**
     * {@inheritDoc}
     */
    public function getSpanIdBinary(): string
    {
        return str_repeat("\0", 8);
    }
}
