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

namespace Displace\XaiSdk\Tests\Unit\Embeddings;

use Displace\XaiSdk\Embeddings\Embedding;
use Displace\XaiSdk\Embeddings\EmbeddingsResponse;
use Displace\XaiSdk\Embeddings\EmbeddingsUsage;
use Displace\XaiSdk\Tests\TestCase;

final class EmbeddingsResponseTest extends TestCase
{
    public function test_constructor_sets_properties(): void
    {
        $embedding = new Embedding(0, [0.1, 0.2, 0.3]);
        $usage = new EmbeddingsUsage(10, 10);

        $response = new EmbeddingsResponse(
            id: 'emb-123',
            model: 'v1',
            data: [$embedding],
            usage: $usage,
            object: 'list',
        );

        $this->assertSame('emb-123', $response->id);
        $this->assertSame('v1', $response->model);
        $this->assertCount(1, $response->data);
        $this->assertSame($usage, $response->usage);
        $this->assertSame('list', $response->object);
    }

    public function test_constructor_uses_default_object(): void
    {
        $response = new EmbeddingsResponse(
            id: 'emb-123',
            model: 'v1',
            data: [],
            usage: new EmbeddingsUsage(),
        );

        $this->assertSame('list', $response->object);
    }

    public function test_getEmbedding_returns_first_vector(): void
    {
        $vector = [0.1, 0.2, 0.3, 0.4, 0.5];
        $embedding = new Embedding(0, $vector);
        $response = new EmbeddingsResponse(
            id: 'test',
            model: 'v1',
            data: [$embedding],
            usage: new EmbeddingsUsage(),
        );

        $this->assertSame($vector, $response->getEmbedding());
    }

    public function test_getEmbedding_returns_null_when_empty(): void
    {
        $response = new EmbeddingsResponse(
            id: 'test',
            model: 'v1',
            data: [],
            usage: new EmbeddingsUsage(),
        );

        $this->assertNull($response->getEmbedding());
    }

    public function test_getEmbeddings_returns_all_vectors(): void
    {
        $vector1 = [0.1, 0.2, 0.3];
        $vector2 = [0.4, 0.5, 0.6];
        $embedding1 = new Embedding(0, $vector1);
        $embedding2 = new Embedding(1, $vector2);

        $response = new EmbeddingsResponse(
            id: 'test',
            model: 'v1',
            data: [$embedding1, $embedding2],
            usage: new EmbeddingsUsage(),
        );

        $embeddings = $response->getEmbeddings();

        $this->assertCount(2, $embeddings);
        $this->assertSame($vector1, $embeddings[0]);
        $this->assertSame($vector2, $embeddings[1]);
    }

    public function test_count_returns_number_of_embeddings(): void
    {
        $embeddings = [
            new Embedding(0, [0.1]),
            new Embedding(1, [0.2]),
            new Embedding(2, [0.3]),
        ];

        $response = new EmbeddingsResponse(
            id: 'test',
            model: 'v1',
            data: $embeddings,
            usage: new EmbeddingsUsage(),
        );

        $this->assertSame(3, $response->count());
    }

    public function test_count_returns_zero_when_empty(): void
    {
        $response = new EmbeddingsResponse(
            id: 'test',
            model: 'v1',
            data: [],
            usage: new EmbeddingsUsage(),
        );

        $this->assertSame(0, $response->count());
    }

    public function test_fromArray_creates_response(): void
    {
        $data = $this->loadJsonFixture('embeddings_response.json');

        $response = EmbeddingsResponse::fromArray($data);

        $this->assertSame('emb-abc123', $response->id);
        $this->assertSame('v1', $response->model);
        $this->assertSame('list', $response->object);
        $this->assertCount(1, $response->data);
        $this->assertSame(10, $response->usage->promptTokens);
        $this->assertSame(10, $response->usage->totalTokens);
    }

    public function test_fromArray_creates_batch_response(): void
    {
        $data = $this->loadJsonFixture('embeddings_batch_response.json');

        $response = EmbeddingsResponse::fromArray($data);

        $this->assertSame('emb-batch123', $response->id);
        $this->assertSame(3, $response->count());

        $embeddings = $response->getEmbeddings();
        $this->assertCount(3, $embeddings);
        $this->assertSame([0.1, 0.2, 0.3, 0.4, 0.5], $embeddings[0]);
        $this->assertSame([0.5, 0.4, 0.3, 0.2, 0.1], $embeddings[1]);
        $this->assertSame([0.3, 0.3, 0.3, 0.3, 0.3], $embeddings[2]);
    }

    public function test_fromArray_handles_missing_fields(): void
    {
        $response = EmbeddingsResponse::fromArray([]);

        $this->assertSame('', $response->id);
        $this->assertSame('', $response->model);
        $this->assertSame('list', $response->object);
        $this->assertEmpty($response->data);
        $this->assertSame(0, $response->usage->promptTokens);
    }

    public function test_response_is_readonly(): void
    {
        $embedding = new Embedding(0, [0.1, 0.2]);
        $usage = new EmbeddingsUsage(5, 5);

        $response = new EmbeddingsResponse(
            id: 'readonly-test',
            model: 'v1',
            data: [$embedding],
            usage: $usage,
        );

        // Verify readonly by accessing properties
        $this->assertSame('readonly-test', $response->id);
        $this->assertSame('v1', $response->model);
        $this->assertSame([$embedding], $response->data);
        $this->assertSame($usage, $response->usage);
    }
}
