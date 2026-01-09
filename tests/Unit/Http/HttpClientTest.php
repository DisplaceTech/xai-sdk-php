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

namespace Displace\XaiSdk\Tests\Unit\Http;

use Displace\XaiSdk\Exceptions\ApiException;
use Displace\XaiSdk\Exceptions\AuthenticationException;
use Displace\XaiSdk\Exceptions\BadRequestException;
use Displace\XaiSdk\Exceptions\RateLimitException;
use Displace\XaiSdk\Exceptions\ServerException;
use Displace\XaiSdk\Http\HttpClient;
use Displace\XaiSdk\Http\HttpConfig;
use Displace\XaiSdk\Tests\TestCase;
use Exception;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

final class HttpClientTest extends TestCase
{
    private ClientInterface&MockObject $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = $this->createMock(ClientInterface::class);
    }

    public function test_constructor_sets_default_base_url(): void
    {
        $client = new HttpClient('test-api-key', $this->mockClient);

        $this->assertEquals('https://api.x.ai/v1', $client->getBaseUrl());
    }

    public function test_constructor_allows_custom_base_url(): void
    {
        $client = new HttpClient(
            'test-api-key',
            $this->mockClient,
            'https://custom.api.com/v2',
        );

        $this->assertEquals('https://custom.api.com/v2', $client->getBaseUrl());
    }

    public function test_get_masked_api_key_masks_long_keys(): void
    {
        $client = new HttpClient('sk-1234567890abcdef', $this->mockClient);

        $masked = $client->getMaskedApiKey();

        $this->assertEquals('sk-1...cdef', $masked);
    }

    public function test_get_masked_api_key_fully_masks_short_keys(): void
    {
        $client = new HttpClient('short', $this->mockClient);

        $masked = $client->getMaskedApiKey();

        $this->assertEquals('*****', $masked);
    }

    public function test_get_request_returns_response(): void
    {
        $expectedBody = json_encode(['data' => 'test']);
        $response = new Response(200, [], $expectedBody);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);
        $result = $client->get('/models');

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_post_request_returns_response(): void
    {
        $expectedBody = json_encode(['id' => 'chatcmpl-123']);
        $response = new Response(200, [], $expectedBody);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);
        $result = $client->post('/chat/completions', ['model' => 'grok-3']);

        $this->assertEquals(200, $result->getStatusCode());
    }

    public function test_request_includes_authorization_header(): void
    {
        $response = new Response(200, [], '{}');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return $request->getHeaderLine('Authorization') === 'Bearer test-api-key';
            }))
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);
        $client->get('/models');
    }

    public function test_request_includes_content_type_header(): void
    {
        $response = new Response(200, [], '{}');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return $request->getHeaderLine('Content-Type') === 'application/json';
            }))
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);
        $client->get('/models');
    }

    public function test_request_includes_user_agent_header(): void
    {
        $response = new Response(200, [], '{}');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return str_contains($request->getHeaderLine('User-Agent'), 'xai-php-sdk');
            }))
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);
        $client->get('/models');
    }

    public function test_400_response_throws_bad_request_exception(): void
    {
        $errorBody = json_encode([
            'error' => [
                'message' => 'Invalid model specified',
                'type' => 'invalid_request_error',
                'param' => 'model',
            ],
        ]);
        $response = new Response(400, [], $errorBody);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid model specified');

        $client->post('/chat/completions', ['model' => 'invalid']);
    }

    public function test_422_response_throws_bad_request_exception(): void
    {
        $errorBody = json_encode([
            'error' => [
                'message' => 'Invalid input: messages is required',
                'type' => 'invalid_request_error',
            ],
        ]);
        $response = new Response(422, [], $errorBody);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid input: messages is required');

        $client->post('/chat/completions', ['model' => 'grok-3']);
    }

    public function test_401_response_throws_authentication_exception(): void
    {
        $errorBody = json_encode([
            'error' => [
                'message' => 'Invalid API key',
                'type' => 'authentication_error',
            ],
        ]);
        $response = new Response(401, [], $errorBody);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('invalid-key', $this->mockClient);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid API key');

        $client->get('/models');
    }

    public function test_429_response_throws_rate_limit_exception(): void
    {
        $errorBody = json_encode([
            'error' => [
                'message' => 'Rate limit exceeded',
                'type' => 'rate_limit_error',
            ],
        ]);
        $response = new Response(429, ['Retry-After' => '30'], $errorBody);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);

        try {
            $client->get('/models');
            $this->fail('Expected RateLimitException was not thrown');
        } catch (RateLimitException $e) {
            $this->assertEquals('Rate limit exceeded', $e->getMessage());
            $this->assertEquals(30, $e->getRetryAfter());
        }
    }

    public function test_500_response_throws_server_exception(): void
    {
        $errorBody = json_encode([
            'error' => [
                'message' => 'Internal server error',
                'type' => 'server_error',
            ],
        ]);
        $response = new Response(500, [], $errorBody);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);

        $this->expectException(ServerException::class);

        $client->get('/models');
    }

    public function test_502_response_throws_server_exception(): void
    {
        $response = new Response(502, [], 'Bad Gateway');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);

        $this->expectException(ServerException::class);

        $client->get('/models');
    }

    public function test_503_response_throws_server_exception(): void
    {
        $response = new Response(503, [], 'Service Unavailable');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);

        $this->expectException(ServerException::class);

        $client->get('/models');
    }

    public function test_403_response_throws_api_exception(): void
    {
        $errorBody = json_encode([
            'error' => [
                'message' => 'Access denied',
                'type' => 'permission_error',
            ],
        ]);
        $response = new Response(403, [], $errorBody);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Access denied');

        $client->get('/models');
    }

    public function test_404_response_throws_api_exception(): void
    {
        $errorBody = json_encode([
            'error' => [
                'message' => 'Model not found',
                'type' => 'not_found_error',
            ],
        ]);
        $response = new Response(404, [], $errorBody);

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);

        $this->expectException(ApiException::class);

        $client->get('/models/unknown');
    }

    public function test_connection_error_throws_api_exception(): void
    {
        $connectionException = new class() extends Exception implements ClientExceptionInterface {};

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willThrowException($connectionException);

        // Use withoutRetries() to ensure only one sendRequest call
        $client = new HttpClient(
            'test-api-key',
            $this->mockClient,
            config: HttpConfig::withoutRetries(),
        );

        try {
            $client->get('/models');
            $this->fail('Expected ApiException was not thrown');
        } catch (ApiException $e) {
            $this->assertEquals('connection_error', $e->getErrorType());
        }
    }

    public function test_delete_request_uses_delete_method(): void
    {
        $response = new Response(204, [], '');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return $request->getMethod() === 'DELETE';
            }))
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);
        $client->delete('/files/file-123');
    }

    public function test_put_request_uses_put_method(): void
    {
        $response = new Response(200, [], '{}');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return $request->getMethod() === 'PUT';
            }))
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);
        $client->put('/resource', ['data' => 'value']);
    }

    public function test_patch_request_uses_patch_method(): void
    {
        $response = new Response(200, [], '{}');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return $request->getMethod() === 'PATCH';
            }))
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);
        $client->patch('/resource', ['data' => 'value']);
    }

    public function test_get_config_returns_http_config(): void
    {
        $config = HttpConfig::withRetries(3);
        $client = new HttpClient('test-api-key', $this->mockClient, config: $config);

        $this->assertSame($config, $client->getConfig());
    }

    public function test_custom_headers_are_included(): void
    {
        $response = new Response(200, [], '{}');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                return $request->getHeaderLine('X-Custom-Header') === 'custom-value';
            }))
            ->willReturn($response);

        $client = new HttpClient(
            'test-api-key',
            $this->mockClient,
            headers: ['X-Custom-Header' => 'custom-value'],
        );
        $client->get('/models');
    }

    public function test_query_parameters_are_appended(): void
    {
        $response = new Response(200, [], '{}');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $uri = (string) $request->getUri();

                return str_contains($uri, 'limit=10') && str_contains($uri, 'offset=20');
            }))
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);
        $client->get('/models', ['limit' => 10, 'offset' => 20]);
    }

    public function test_post_body_is_json_encoded(): void
    {
        $response = new Response(200, [], '{}');

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $body = $request->getBody()->getContents();
                $decoded = json_decode($body, true);

                return $decoded['model'] === 'grok-3' && $decoded['messages'][0]['role'] === 'user';
            }))
            ->willReturn($response);

        $client = new HttpClient('test-api-key', $this->mockClient);
        $client->post('/chat/completions', [
            'model' => 'grok-3',
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);
    }
}
