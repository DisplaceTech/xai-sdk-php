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

use Displace\XaiSdk\Exceptions\BadRequestException;
use Displace\XaiSdk\Exceptions\XaiException;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class BadRequestExceptionTest extends TestCase
{
    public function test_extends_xai_exception(): void
    {
        $exception = new BadRequestException();

        $this->assertInstanceOf(XaiException::class, $exception);
    }

    public function test_has_default_message(): void
    {
        $exception = new BadRequestException();

        $this->assertEquals('Bad request. Please check your request parameters.', $exception->getMessage());
    }

    public function test_has_400_code(): void
    {
        $exception = new BadRequestException();

        $this->assertEquals(400, $exception->getCode());
    }

    public function test_stores_parameter_name(): void
    {
        $exception = new BadRequestException('Invalid model', 'model');

        $this->assertEquals('model', $exception->getParameter());
    }

    public function test_from_response_extracts_message(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat/completions');
        $response = new Response(400, [], json_encode([
            'error' => [
                'message' => 'Invalid model: unknown-model',
                'type' => 'invalid_request_error',
                'param' => 'model',
            ],
        ]));

        $exception = BadRequestException::fromResponse($request, $response);

        $this->assertEquals('Invalid model: unknown-model', $exception->getMessage());
        $this->assertEquals('model', $exception->getParameter());
    }

    public function test_from_response_handles_simple_format(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat/completions');
        $response = new Response(400, [], json_encode([
            'message' => 'Invalid request',
            'parameter' => 'temperature',
        ]));

        $exception = BadRequestException::fromResponse($request, $response);

        $this->assertEquals('Invalid request', $exception->getMessage());
        $this->assertEquals('temperature', $exception->getParameter());
    }

    public function test_from_response_handles_detail_format(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat/completions');
        $response = new Response(400, [], json_encode([
            'detail' => 'Temperature must be between 0 and 2',
        ]));

        $exception = BadRequestException::fromResponse($request, $response);

        $this->assertEquals('Temperature must be between 0 and 2', $exception->getMessage());
    }

    public function test_from_response_uses_default_for_empty_body(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat/completions');
        $response = new Response(400, [], '{}');

        $exception = BadRequestException::fromResponse($request, $response);

        $this->assertEquals('Bad request. Please check your request parameters.', $exception->getMessage());
    }

    public function test_get_parameter_returns_null_when_not_set(): void
    {
        $exception = new BadRequestException('Error occurred');

        $this->assertNull($exception->getParameter());
    }
}
