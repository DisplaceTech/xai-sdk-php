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

use Displace\XaiSdk\Exceptions\XaiException;
use Displace\XaiSdk\Tests\TestCase;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

final class XaiExceptionTest extends TestCase
{
    public function test_extends_exception(): void
    {
        $exception = new XaiException('Test error');

        $this->assertInstanceOf(Exception::class, $exception);
    }

    public function test_message_is_set(): void
    {
        $exception = new XaiException('Something went wrong');

        $this->assertSame('Something went wrong', $exception->getMessage());
    }

    public function test_code_is_set(): void
    {
        $exception = new XaiException('Error', 500);

        $this->assertSame(500, $exception->getCode());
    }

    public function test_previous_exception_is_set(): void
    {
        $previous = new Exception('Original error');
        $exception = new XaiException('Wrapped error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_request_is_null_by_default(): void
    {
        $exception = new XaiException('Error');

        $this->assertNull($exception->request);
    }

    public function test_response_is_null_by_default(): void
    {
        $exception = new XaiException('Error');

        $this->assertNull($exception->response);
    }

    public function test_request_can_be_set(): void
    {
        $request = new Request('GET', 'https://api.x.ai/v1/chat');
        $exception = new XaiException('Error', 0, null, $request);

        $this->assertSame($request, $exception->request);
    }

    public function test_response_can_be_set(): void
    {
        $response = new Response(200, [], '{"status": "ok"}');
        $exception = new XaiException('Error', 0, null, null, $response);

        $this->assertSame($response, $exception->response);
    }

    public function test_getHttpStatusCode_returns_null_without_response(): void
    {
        $exception = new XaiException('Error');

        $this->assertNull($exception->getHttpStatusCode());
    }

    public function test_getHttpStatusCode_returns_status_from_response(): void
    {
        $response = new Response(404);
        $exception = new XaiException('Not found', 404, null, null, $response);

        $this->assertSame(404, $exception->getHttpStatusCode());
    }

    public function test_getResponseBody_returns_null_without_response(): void
    {
        $exception = new XaiException('Error');

        $this->assertNull($exception->getResponseBody());
    }

    public function test_getResponseBody_returns_body_content(): void
    {
        $body = '{"error": "something failed"}';
        $response = new Response(500, [], $body);
        $exception = new XaiException('Error', 500, null, null, $response);

        $this->assertSame($body, $exception->getResponseBody());
    }

    public function test_getResponseData_returns_null_without_response(): void
    {
        $exception = new XaiException('Error');

        $this->assertNull($exception->getResponseData());
    }

    public function test_getResponseData_returns_null_for_invalid_json(): void
    {
        $response = new Response(500, [], 'not valid json');
        $exception = new XaiException('Error', 500, null, null, $response);

        $this->assertNull($exception->getResponseData());
    }

    public function test_getResponseData_returns_parsed_json(): void
    {
        $data = ['error' => 'test', 'code' => 'ERR_001'];
        $response = new Response(400, [], json_encode($data));
        $exception = new XaiException('Error', 400, null, null, $response);

        $this->assertSame($data, $exception->getResponseData());
    }

    public function test_request_and_response_are_readonly(): void
    {
        $request = new Request('POST', 'https://api.x.ai/v1/chat');
        $response = new Response(200);
        $exception = new XaiException('Test', 0, null, $request, $response);

        // Verify readonly by accessing the properties
        $this->assertSame($request, $exception->request);
        $this->assertSame($response, $exception->response);
    }
}
