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
use OpenTelemetry\API\Trace\TracerInterface;

/**
 * A no-operation tracer that creates no-op spans.
 *
 * Used when tracing is disabled via XAI_SDK_DISABLE_TRACING environment variable.
 */
class NoOpTracer implements TracerInterface
{
    /**
     * {@inheritDoc}
     */
    public function spanBuilder(string $spanName): SpanBuilderInterface
    {
        return new NoOpSpanBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): bool
    {
        return false;
    }
}
