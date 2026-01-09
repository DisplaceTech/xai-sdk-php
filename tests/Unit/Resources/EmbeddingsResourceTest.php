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

namespace Displace\XaiSdk\Tests\Unit\Resources;

use Displace\XaiSdk\Embeddings\EmbeddingsResponse;
use Displace\XaiSdk\Http\HttpClient;
use Displace\XaiSdk\Resources\EmbeddingsResource;
use Displace\XaiSdk\Tests\TestCase;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Client\ClientInterface;

final class EmbeddingsResourceTest extends TestCase
{
    private ClientInterface&MockObject $mockClient;

    private HttpClient $httpClient;

    private EmbeddingsResource $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = $this->createMock(ClientInterface::class);
        $this->httpClient = new HttpClient('test-api-key', $this->mockClient);
        $this->resource = new EmbeddingsResource($this->httpClient);
    }

    public function test_create_with_single_string_input(): void
    {
        $responseData = $this->loadJsonFixture('embeddings_response.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = json_decode($request->getBody()->getContents(), true);

                return $body['model'] === 'v1'
                    && $body['input'] === 'Hello, world!'
                    && ! isset($body['encoding_format'])
                    && ! isset($body['dimensions']);
            }))
            ->willReturn($response);

        $result = $this->resource->create(
            model: 'v1',
            input: 'Hello, world!',
        );

        $this->assertInstanceOf(EmbeddingsResponse::class, $result);
        $this->assertSame('emb-abc123', $result->id);
        $this->assertSame('v1', $result->model);
        $this->assertCount(1, $result->data);
    }

    public function test_create_with_array_input(): void
    {
        $responseData = $this->loadJsonFixture('embeddings_batch_response.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = json_decode($request->getBody()->getContents(), true);

                return $body['model'] === 'v1'
                    && is_array($body['input'])
                    && count($body['input']) === 3;
            }))
            ->willReturn($response);

        $result = $this->resource->create(
            model: 'v1',
            input: ['Text 1', 'Text 2', 'Text 3'],
        );

        $this->assertInstanceOf(EmbeddingsResponse::class, $result);
        $this->assertCount(3, $result->data);
    }

    public function test_create_with_encoding_format(): void
    {
        $responseData = $this->loadJsonFixture('embeddings_response.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = json_decode($request->getBody()->getContents(), true);

                return $body['encoding_format'] === 'base64';
            }))
            ->willReturn($response);

        $result = $this->resource->create(
            model: 'v1',
            input: 'Test text',
            encodingFormat: 'base64',
        );

        $this->assertInstanceOf(EmbeddingsResponse::class, $result);
    }

    public function test_create_with_dimensions(): void
    {
        $responseData = $this->loadJsonFixture('embeddings_response.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = json_decode($request->getBody()->getContents(), true);

                return $body['dimensions'] === 512;
            }))
            ->willReturn($response);

        $result = $this->resource->create(
            model: 'v1',
            input: 'Test text',
            dimensions: 512,
        );

        $this->assertInstanceOf(EmbeddingsResponse::class, $result);
    }

    public function test_create_with_all_options(): void
    {
        $responseData = $this->loadJsonFixture('embeddings_response.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $body = json_decode($request->getBody()->getContents(), true);

                return $body['model'] === 'v1'
                    && $body['input'] === 'Test'
                    && $body['encoding_format'] === 'float'
                    && $body['dimensions'] === 256;
            }))
            ->willReturn($response);

        $result = $this->resource->create(
            model: 'v1',
            input: 'Test',
            encodingFormat: 'float',
            dimensions: 256,
        );

        $this->assertInstanceOf(EmbeddingsResponse::class, $result);
    }

    public function test_create_uses_correct_endpoint(): void
    {
        $responseData = $this->loadJsonFixture('embeddings_response.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                $uri = (string) $request->getUri();

                return str_contains($uri, '/embeddings');
            }))
            ->willReturn($response);

        $this->resource->create(
            model: 'v1',
            input: 'Test',
        );
    }

    public function test_create_uses_post_method(): void
    {
        $responseData = $this->loadJsonFixture('embeddings_response.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                return $request->getMethod() === 'POST';
            }))
            ->willReturn($response);

        $this->resource->create(
            model: 'v1',
            input: 'Test',
        );
    }

    public function test_response_contains_correct_usage(): void
    {
        $responseData = $this->loadJsonFixture('embeddings_response.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $result = $this->resource->create(
            model: 'v1',
            input: 'Test',
        );

        $this->assertSame(10, $result->usage->promptTokens);
        $this->assertSame(10, $result->usage->totalTokens);
    }

    public function test_batch_response_contains_all_embeddings(): void
    {
        $responseData = $this->loadJsonFixture('embeddings_batch_response.json');
        $response = new Response(200, [], json_encode($responseData));

        $this->mockClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response);

        $result = $this->resource->create(
            model: 'v1',
            input: ['a', 'b', 'c'],
        );

        $this->assertSame(3, $result->count());
        $embeddings = $result->getEmbeddings();
        $this->assertCount(3, $embeddings);
        $this->assertCount(5, $embeddings[0]);
    }
}
