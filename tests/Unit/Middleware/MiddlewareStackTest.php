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

namespace Displace\XaiSdk\Tests\Unit\Middleware;

use Displace\XaiSdk\Middleware\MiddlewareInterface;
use Displace\XaiSdk\Middleware\MiddlewareStack;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MiddlewareStackTest extends TestCase
{
    public function test_empty_stack(): void
    {
        $stack = new MiddlewareStack();

        $this->assertSame(0, $stack->count());
        $this->assertSame([], $stack->all());
    }

    public function test_push_middleware(): void
    {
        $stack = new MiddlewareStack();
        $middleware = $this->createMiddleware();

        $stack->push($middleware);

        $this->assertSame(1, $stack->count());
        $this->assertSame([$middleware], $stack->all());
    }

    public function test_prepend_middleware(): void
    {
        $stack = new MiddlewareStack();
        $first = $this->createMiddleware();
        $second = $this->createMiddleware();

        $stack->push($first);
        $stack->prepend($second);

        $all = $stack->all();
        $this->assertSame($second, $all[0]);
        $this->assertSame($first, $all[1]);
    }

    public function test_remove_middleware(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                return $next($request);
            }
        };

        $stack = new MiddlewareStack();
        $stack->push($middleware);

        $this->assertTrue($stack->has($middleware::class));

        $stack->remove($middleware::class);

        $this->assertFalse($stack->has($middleware::class));
        $this->assertSame(0, $stack->count());
    }

    public function test_has_middleware(): void
    {
        $middleware = new class implements MiddlewareInterface {
            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                return $next($request);
            }
        };

        $stack = new MiddlewareStack();

        $this->assertFalse($stack->has($middleware::class));

        $stack->push($middleware);

        $this->assertTrue($stack->has($middleware::class));
    }

    public function test_clear_stack(): void
    {
        $stack = new MiddlewareStack();
        $stack->push($this->createMiddleware());
        $stack->push($this->createMiddleware());

        $stack->clear();

        $this->assertSame(0, $stack->count());
    }

    public function test_handle_with_no_middleware(): void
    {
        $stack = new MiddlewareStack();
        $request = new Request('GET', 'https://example.com');
        $response = new Response(200);

        $result = $stack->handle($request, fn ($req) => $response);

        $this->assertSame($response, $result);
    }

    public function test_handle_with_single_middleware(): void
    {
        $stack = new MiddlewareStack();

        $middleware = new class implements MiddlewareInterface {
            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                $request = $request->withHeader('X-Test', 'value');

                return $next($request);
            }
        };

        $stack->push($middleware);

        $request = new Request('GET', 'https://example.com');
        $capturedRequest = null;

        $stack->handle($request, function ($req) use (&$capturedRequest) {
            $capturedRequest = $req;

            return new Response(200);
        });

        $this->assertSame('value', $capturedRequest->getHeaderLine('X-Test'));
    }

    public function test_middleware_execution_order(): void
    {
        $stack = new MiddlewareStack();
        $order = [];

        $first = new class ($order) implements MiddlewareInterface {
            public function __construct(private array &$order)
            {
            }

            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                $this->order[] = 'first-before';
                $response = $next($request);
                $this->order[] = 'first-after';

                return $response;
            }
        };

        $second = new class ($order) implements MiddlewareInterface {
            public function __construct(private array &$order)
            {
            }

            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                $this->order[] = 'second-before';
                $response = $next($request);
                $this->order[] = 'second-after';

                return $response;
            }
        };

        $stack->push($first);
        $stack->push($second);

        $request = new Request('GET', 'https://example.com');
        $stack->handle($request, function ($req) use (&$order) {
            $order[] = 'handler';

            return new Response(200);
        });

        $this->assertSame([
            'first-before',
            'second-before',
            'handler',
            'second-after',
            'first-after',
        ], $order);
    }

    public function test_middleware_can_short_circuit(): void
    {
        $stack = new MiddlewareStack();
        $handlerCalled = false;

        $shortCircuit = new class implements MiddlewareInterface {
            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                return new Response(401, [], 'Unauthorized');
            }
        };

        $stack->push($shortCircuit);

        $request = new Request('GET', 'https://example.com');
        $result = $stack->handle($request, function ($req) use (&$handlerCalled) {
            $handlerCalled = true;

            return new Response(200);
        });

        $this->assertFalse($handlerCalled);
        $this->assertSame(401, $result->getStatusCode());
    }

    public function test_wrap_handler(): void
    {
        $stack = new MiddlewareStack();

        $middleware = new class implements MiddlewareInterface {
            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                $response = $next($request);

                return $response->withHeader('X-Wrapped', 'true');
            }
        };

        $stack->push($middleware);

        $handler = fn ($req) => new Response(200);
        $wrapped = $stack->wrap($handler);

        $request = new Request('GET', 'https://example.com');
        $response = $wrapped($request);

        $this->assertSame('true', $response->getHeaderLine('X-Wrapped'));
    }

    public function test_static_empty(): void
    {
        $stack = MiddlewareStack::empty();

        $this->assertInstanceOf(MiddlewareStack::class, $stack);
        $this->assertSame(0, $stack->count());
    }

    public function test_static_of(): void
    {
        $middleware = $this->createMiddleware();
        $stack = MiddlewareStack::of([$middleware]);

        $this->assertSame(1, $stack->count());
        $this->assertSame([$middleware], $stack->all());
    }

    public function test_fluent_interface(): void
    {
        $stack = new MiddlewareStack();
        $middleware = $this->createMiddleware();

        $result = $stack->push($middleware)->clear()->push($middleware);

        $this->assertSame($stack, $result);
    }

    private function createMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(RequestInterface $request, callable $next): ResponseInterface
            {
                return $next($request);
            }
        };
    }
}
