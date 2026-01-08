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

use Displace\XaiSdk\Exceptions\ServerException;
use Displace\XaiSdk\Exceptions\XaiException;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class ServerExceptionTest extends TestCase
{
    public function test_extends_xai_exception(): void
    {
        $exception = new ServerException();

        $this->assertInstanceOf(XaiException::class, $exception);
    }

    public function test_has_default_message(): void
    {
        $exception = new ServerException();

        $this->assertEquals('A server error occurred. Please try again later.', $exception->getMessage());
    }

    public function test_has_500_code_by_default(): void
    {
        $exception = new ServerException();

        $this->assertEquals(500, $exception->getCode());
    }

    public function test_is_retryable_by_default(): void
    {
        $exception = new ServerException();

        $this->assertTrue($exception->isRetryable());
    }

    public function test_accepts_custom_retryable_flag(): void
    {
        $exception = new ServerException(retryable: false);

        $this->assertFalse($exception->isRetryable());
    }

    public function test_from_response_500(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $response = new Response(500, [], json_encode([
            'error' => [
                'message' => 'Internal server error',
                'type' => 'server_error',
            ],
        ]));

        $exception = ServerException::fromResponse($request, $response);

        $this->assertEquals(500, $exception->getCode());
        $this->assertEquals('Internal server error', $exception->getMessage());
        $this->assertTrue($exception->isRetryable());
    }

    public function test_from_response_502(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $response = new Response(502, [], '');

        $exception = ServerException::fromResponse($request, $response);

        $this->assertEquals(502, $exception->getCode());
        $this->assertStringContainsString('Bad gateway', $exception->getMessage());
        $this->assertTrue($exception->isRetryable());
    }

    public function test_from_response_503(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $response = new Response(503, [], '');

        $exception = ServerException::fromResponse($request, $response);

        $this->assertEquals(503, $exception->getCode());
        $this->assertStringContainsString('Service unavailable', $exception->getMessage());
        $this->assertTrue($exception->isRetryable());
    }

    public function test_from_response_504(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $response = new Response(504, [], '');

        $exception = ServerException::fromResponse($request, $response);

        $this->assertEquals(504, $exception->getCode());
        $this->assertStringContainsString('Gateway timeout', $exception->getMessage());
        $this->assertTrue($exception->isRetryable());
    }

    public function test_from_response_501_is_not_retryable(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $response = new Response(501, [], '');

        $exception = ServerException::fromResponse($request, $response);

        $this->assertEquals(501, $exception->getCode());
        $this->assertFalse($exception->isRetryable());
    }

    public function test_from_response_extracts_error_message(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $response = new Response(500, [], json_encode([
            'message' => 'Custom server error message',
        ]));

        $exception = ServerException::fromResponse($request, $response);

        $this->assertEquals('Custom server error message', $exception->getMessage());
    }

    public function test_from_response_uses_default_for_empty_body(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $response = new Response(500, [], '{}');

        $exception = ServerException::fromResponse($request, $response);

        $this->assertStringContainsString('Internal server error', $exception->getMessage());
    }
}
