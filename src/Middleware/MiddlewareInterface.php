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

namespace Displace\XaiSdk\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface for HTTP middleware.
 *
 * Middleware can intercept and modify requests before they are sent,
 * and responses after they are received. This follows a PSR-15 inspired pattern
 * adapted for the SDK's use case.
 *
 * @example
 * ```php
 * class MyMiddleware implements MiddlewareInterface
 * {
 *     public function process(RequestInterface $request, callable $next): ResponseInterface
 *     {
 *         // Modify request
 *         $request = $request->withHeader('X-Custom', 'value');
 *
 *         // Call next middleware
 *         $response = $next($request);
 *
 *         // Modify response
 *         return $response->withHeader('X-Processed', 'true');
 *     }
 * }
 * ```
 */
interface MiddlewareInterface
{
    /**
     * Process a request and produce a response.
     *
     * The middleware should call the next handler to continue processing,
     * or return a response directly to short-circuit the chain.
     *
     * @param RequestInterface $request The HTTP request.
     * @param callable(RequestInterface): ResponseInterface $next The next handler.
     *
     * @return ResponseInterface The HTTP response.
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface;
}
