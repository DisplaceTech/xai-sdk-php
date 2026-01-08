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

use Displace\XaiSdk\Exceptions\AuthenticationException;
use Displace\XaiSdk\Exceptions\XaiException;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class AuthenticationExceptionTest extends TestCase
{
    public function test_extends_xai_exception(): void
    {
        $exception = new AuthenticationException();

        $this->assertInstanceOf(XaiException::class, $exception);
    }

    public function test_default_message(): void
    {
        $exception = new AuthenticationException();

        $this->assertStringContainsString('Authentication failed', $exception->getMessage());
        $this->assertStringContainsString('API key', $exception->getMessage());
    }

    public function test_custom_message(): void
    {
        $exception = new AuthenticationException('Invalid API key provided');

        $this->assertSame('Invalid API key provided', $exception->getMessage());
    }

    public function test_code_is_401(): void
    {
        $exception = new AuthenticationException();

        $this->assertSame(401, $exception->getCode());
    }

    public function test_request_can_be_set(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $exception = new AuthenticationException('Auth error', null, $request);

        $this->assertSame($request, $exception->request);
    }

    public function test_response_can_be_set(): void
    {
        $response = new Response(401, [], '{"error": "unauthorized"}');
        $exception = new AuthenticationException('Auth error', null, null, $response);

        $this->assertSame($response, $exception->response);
    }

    public function test_fromResponse_creates_exception(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $responseBody = json_encode([
            'error' => [
                'message' => 'Invalid API key',
                'type' => 'authentication_error',
            ],
        ]);
        $response = new Response(401, [], $responseBody);

        $exception = AuthenticationException::fromResponse($request, $response);

        $this->assertSame('Invalid API key', $exception->getMessage());
        $this->assertSame(401, $exception->getCode());
        $this->assertSame($request, $exception->request);
        $this->assertSame($response, $exception->response);
    }

    public function test_fromResponse_uses_message_field(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $responseBody = json_encode(['message' => 'API key expired']);
        $response = new Response(401, [], $responseBody);

        $exception = AuthenticationException::fromResponse($request, $response);

        $this->assertSame('API key expired', $exception->getMessage());
    }

    public function test_fromResponse_uses_default_for_empty_body(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $response = new Response(401, [], '{}');

        $exception = AuthenticationException::fromResponse($request, $response);

        $this->assertStringContainsString('Authentication failed', $exception->getMessage());
    }

    public function test_fromResponse_handles_non_json_body(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/models');
        $response = new Response(401, [], 'Unauthorized');

        $exception = AuthenticationException::fromResponse($request, $response);

        $this->assertStringContainsString('Authentication failed', $exception->getMessage());
    }
}
