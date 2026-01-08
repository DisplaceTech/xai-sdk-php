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

use Displace\XaiSdk\Exceptions\ApiException;
use Displace\XaiSdk\Exceptions\XaiException;
use Displace\XaiSdk\Tests\TestCase;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class ApiExceptionTest extends TestCase
{
    public function test_extends_xai_exception(): void
    {
        $exception = new ApiException('API error');

        $this->assertInstanceOf(XaiException::class, $exception);
    }

    public function test_message_is_set(): void
    {
        $exception = new ApiException('Something went wrong');

        $this->assertSame('Something went wrong', $exception->getMessage());
    }

    public function test_code_is_set(): void
    {
        $exception = new ApiException('Error', 400);

        $this->assertSame(400, $exception->getCode());
    }

    public function test_errorType_is_null_by_default(): void
    {
        $exception = new ApiException('Error');

        $this->assertNull($exception->errorType);
    }

    public function test_errorCode_is_null_by_default(): void
    {
        $exception = new ApiException('Error');

        $this->assertNull($exception->errorCode);
    }

    public function test_errorType_can_be_set(): void
    {
        $exception = new ApiException('Error', 400, 'invalid_request_error');

        $this->assertSame('invalid_request_error', $exception->errorType);
    }

    public function test_errorCode_can_be_set(): void
    {
        $exception = new ApiException('Error', 400, null, 'model_not_found');

        $this->assertSame('model_not_found', $exception->errorCode);
    }

    public function test_getErrorType_returns_value(): void
    {
        $exception = new ApiException('Error', 400, 'validation_error');

        $this->assertSame('validation_error', $exception->getErrorType());
    }

    public function test_getErrorCode_returns_value(): void
    {
        $exception = new ApiException('Error', 400, null, 'ERR_001');

        $this->assertSame('ERR_001', $exception->getErrorCode());
    }

    public function test_fromResponse_with_openai_error_format(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $responseBody = json_encode([
            'error' => [
                'message' => 'Invalid model specified',
                'type' => 'invalid_request_error',
                'code' => 'model_not_found',
            ],
        ]);
        $response = new Response(400, [], $responseBody);

        $exception = ApiException::fromResponse($request, $response);

        $this->assertSame('Invalid model specified', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame('invalid_request_error', $exception->errorType);
        $this->assertSame('model_not_found', $exception->errorCode);
    }

    public function test_fromResponse_with_simple_format(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $responseBody = json_encode([
            'message' => 'Bad request',
            'type' => 'error',
            'code' => 'BAD_REQUEST',
        ]);
        $response = new Response(400, [], $responseBody);

        $exception = ApiException::fromResponse($request, $response);

        $this->assertSame('Bad request', $exception->getMessage());
        $this->assertSame('error', $exception->errorType);
        $this->assertSame('BAD_REQUEST', $exception->errorCode);
    }

    public function test_fromResponse_with_detail_field(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $responseBody = json_encode(['detail' => 'Detailed error message']);
        $response = new Response(422, [], $responseBody);

        $exception = ApiException::fromResponse($request, $response);

        $this->assertSame('Detailed error message', $exception->getMessage());
    }

    public function test_fromResponse_with_empty_body(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $response = new Response(500, [], '{}');

        $exception = ApiException::fromResponse($request, $response);

        $this->assertStringContainsString('unknown', strtolower($exception->getMessage()));
    }

    public function test_connectionError_creates_exception(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $exception = ApiException::connectionError('Could not resolve host', $request);

        $this->assertStringContainsString('Connection error', $exception->getMessage());
        $this->assertStringContainsString('Could not resolve host', $exception->getMessage());
        $this->assertSame('connection_error', $exception->errorType);
        $this->assertSame($request, $exception->request);
        $this->assertNull($exception->response);
    }

    public function test_connectionError_with_previous_exception(): void
    {
        $previous = new Exception('DNS lookup failed');
        $exception = ApiException::connectionError('DNS error', null, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_timeout_creates_exception(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $exception = ApiException::timeout($request);

        $this->assertStringContainsString('timed out', $exception->getMessage());
        $this->assertSame('timeout_error', $exception->errorType);
        $this->assertSame($request, $exception->request);
    }

    public function test_timeout_with_previous_exception(): void
    {
        $previous = new Exception('Socket timeout');
        $exception = ApiException::timeout(null, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_errorType_is_readonly(): void
    {
        $exception = new ApiException('Error', 400, 'test_type', 'test_code');

        $this->assertSame('test_type', $exception->errorType);
        $this->assertSame('test_code', $exception->errorCode);
    }
}
