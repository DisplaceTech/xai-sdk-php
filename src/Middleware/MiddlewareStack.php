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
 * Manages a stack of middleware for request/response processing.
 *
 * Middleware is executed in FIFO order for requests, and LIFO order for responses.
 * This creates an "onion" pattern where each middleware wraps the next.
 *
 * @example
 * ```php
 * $stack = new MiddlewareStack();
 * $stack->push(new LoggingMiddleware($logger));
 * $stack->push(new CachingMiddleware($cache));
 *
 * $response = $stack->handle($request, $finalHandler);
 * ```
 */
final class MiddlewareStack
{
    /** @var array<MiddlewareInterface> */
    private array $middleware = [];

    /**
     * Creates a new middleware stack.
     *
     * @param array<MiddlewareInterface> $middleware Initial middleware.
     */
    public function __construct(array $middleware = [])
    {
        foreach ($middleware as $m) {
            $this->push($m);
        }
    }

    /**
     * Adds middleware to the end of the stack.
     *
     * Middleware added later will be executed last for requests,
     * and first for responses (outer layer of the onion).
     *
     * @param MiddlewareInterface $middleware The middleware to add.
     *
     * @return $this
     */
    public function push(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    /**
     * Adds middleware to the beginning of the stack.
     *
     * Middleware prepended will be executed first for requests,
     * and last for responses (inner layer of the onion).
     *
     * @param MiddlewareInterface $middleware The middleware to add.
     *
     * @return $this
     */
    public function prepend(MiddlewareInterface $middleware): self
    {
        array_unshift($this->middleware, $middleware);

        return $this;
    }

    /**
     * Removes all instances of a middleware class.
     *
     * @param class-string<MiddlewareInterface> $class The middleware class to remove.
     *
     * @return $this
     */
    public function remove(string $class): self
    {
        $this->middleware = array_filter(
            $this->middleware,
            fn (MiddlewareInterface $m) => ! $m instanceof $class,
        );

        return $this;
    }

    /**
     * Checks if the stack contains a middleware of the given class.
     *
     * @param class-string<MiddlewareInterface> $class The middleware class.
     *
     * @return bool
     */
    public function has(string $class): bool
    {
        foreach ($this->middleware as $m) {
            if ($m instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets all middleware in the stack.
     *
     * @return array<MiddlewareInterface>
     */
    public function all(): array
    {
        return $this->middleware;
    }

    /**
     * Returns the number of middleware in the stack.
     */
    public function count(): int
    {
        return count($this->middleware);
    }

    /**
     * Clears all middleware from the stack.
     *
     * @return $this
     */
    public function clear(): self
    {
        $this->middleware = [];

        return $this;
    }

    /**
     * Handles a request through the middleware stack.
     *
     * @param RequestInterface $request The HTTP request.
     * @param callable(RequestInterface): ResponseInterface $finalHandler The final handler.
     *
     * @return ResponseInterface The HTTP response.
     */
    public function handle(RequestInterface $request, callable $finalHandler): ResponseInterface
    {
        // Build the middleware chain from the inside out
        $chain = $finalHandler;

        foreach (array_reverse($this->middleware) as $middleware) {
            $chain = fn (RequestInterface $req) => $middleware->process($req, $chain);
        }

        return $chain($request);
    }

    /**
     * Creates a new handler that wraps the given handler with this middleware stack.
     *
     * This is useful for creating reusable handler pipelines.
     *
     * @param callable(RequestInterface): ResponseInterface $handler The handler to wrap.
     *
     * @return callable(RequestInterface): ResponseInterface The wrapped handler.
     */
    public function wrap(callable $handler): callable
    {
        return fn (RequestInterface $request) => $this->handle($request, $handler);
    }

    /**
     * Creates an empty middleware stack.
     *
     * @return self
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Creates a middleware stack from an array of middleware.
     *
     * @param array<MiddlewareInterface> $middleware The middleware.
     *
     * @return self
     */
    public static function of(array $middleware): self
    {
        return new self($middleware);
    }
}
