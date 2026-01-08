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

namespace Displace\XaiSdk\Tests\Unit\Exceptions;

use Displace\XaiSdk\Exceptions\RateLimitException;
use Displace\XaiSdk\Exceptions\XaiException;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class RateLimitExceptionTest extends TestCase
{
    public function test_extends_xai_exception(): void
    {
        $exception = new RateLimitException();

        $this->assertInstanceOf(XaiException::class, $exception);
    }

    public function test_default_message(): void
    {
        $exception = new RateLimitException();

        $this->assertStringContainsString('Rate limit exceeded', $exception->getMessage());
    }

    public function test_custom_message(): void
    {
        $exception = new RateLimitException('Too many requests');

        $this->assertSame('Too many requests', $exception->getMessage());
    }

    public function test_code_is_429(): void
    {
        $exception = new RateLimitException();

        $this->assertSame(429, $exception->getCode());
    }

    public function test_retryAfter_is_null_by_default(): void
    {
        $exception = new RateLimitException();

        $this->assertNull($exception->retryAfter);
    }

    public function test_retryAfter_can_be_set(): void
    {
        $exception = new RateLimitException('Rate limited', 60);

        $this->assertSame(60, $exception->retryAfter);
    }

    public function test_getRetryAfter_returns_value(): void
    {
        $exception = new RateLimitException('Rate limited', 30);

        $this->assertSame(30, $exception->getRetryAfter());
    }

    public function test_fromResponse_parses_retry_after_header(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $response = new Response(429, ['Retry-After' => '120'], '{}');

        $exception = RateLimitException::fromResponse($request, $response);

        $this->assertSame(120, $exception->retryAfter);
    }

    public function test_fromResponse_parses_retry_after_from_body(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $responseBody = json_encode(['retry_after' => 45]);
        $response = new Response(429, [], $responseBody);

        $exception = RateLimitException::fromResponse($request, $response);

        $this->assertSame(45, $exception->retryAfter);
    }

    public function test_fromResponse_prefers_header_over_body(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $responseBody = json_encode(['retry_after' => 30]);
        $response = new Response(429, ['Retry-After' => '60'], $responseBody);

        $exception = RateLimitException::fromResponse($request, $response);

        // Header should take precedence
        $this->assertSame(60, $exception->retryAfter);
    }

    public function test_fromResponse_uses_error_message(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $responseBody = json_encode([
            'error' => [
                'message' => 'You have exceeded your rate limit',
            ],
        ]);
        $response = new Response(429, [], $responseBody);

        $exception = RateLimitException::fromResponse($request, $response);

        $this->assertSame('You have exceeded your rate limit', $exception->getMessage());
    }

    public function test_fromResponse_uses_message_field(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $responseBody = json_encode(['message' => 'Slow down!']);
        $response = new Response(429, [], $responseBody);

        $exception = RateLimitException::fromResponse($request, $response);

        $this->assertSame('Slow down!', $exception->getMessage());
    }

    public function test_fromResponse_includes_request_and_response(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $response = new Response(429, [], '{}');

        $exception = RateLimitException::fromResponse($request, $response);

        $this->assertSame($request, $exception->request);
        $this->assertSame($response, $exception->response);
    }

    public function test_retryAfter_is_readonly(): void
    {
        $exception = new RateLimitException('Rate limited', 90);

        $this->assertSame(90, $exception->retryAfter);
    }
}
