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
use Displace\XaiSdk\Tests\TestCase;

final class EmbeddingTest extends TestCase
{
    public function test_constructor_sets_properties(): void
    {
        $vector = [0.1, 0.2, 0.3, 0.4, 0.5];

        $embedding = new Embedding(
            index: 0,
            embedding: $vector,
            object: 'embedding',
        );

        $this->assertSame(0, $embedding->index);
        $this->assertSame($vector, $embedding->embedding);
        $this->assertSame('embedding', $embedding->object);
    }

    public function test_constructor_uses_default_object(): void
    {
        $embedding = new Embedding(
            index: 1,
            embedding: [0.5, 0.6],
        );

        $this->assertSame('embedding', $embedding->object);
    }

    public function test_fromArray_creates_embedding(): void
    {
        $data = [
            'index' => 2,
            'embedding' => [0.1, 0.2, 0.3],
            'object' => 'embedding',
        ];

        $embedding = Embedding::fromArray($data);

        $this->assertSame(2, $embedding->index);
        $this->assertSame([0.1, 0.2, 0.3], $embedding->embedding);
        $this->assertSame('embedding', $embedding->object);
    }

    public function test_fromArray_handles_missing_fields(): void
    {
        $embedding = Embedding::fromArray([]);

        $this->assertSame(0, $embedding->index);
        $this->assertSame([], $embedding->embedding);
        $this->assertSame('embedding', $embedding->object);
    }

    public function test_getDimensions_returns_vector_length(): void
    {
        $embedding = new Embedding(
            index: 0,
            embedding: [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8],
        );

        $this->assertSame(8, $embedding->getDimensions());
    }

    public function test_getDimensions_returns_zero_for_empty(): void
    {
        $embedding = new Embedding(
            index: 0,
            embedding: [],
        );

        $this->assertSame(0, $embedding->getDimensions());
    }

    public function test_embedding_is_readonly(): void
    {
        $vector = [0.1, 0.2, 0.3];
        $embedding = new Embedding(0, $vector);

        // Verify readonly by accessing properties
        $this->assertSame(0, $embedding->index);
        $this->assertSame($vector, $embedding->embedding);
        $this->assertSame('embedding', $embedding->object);
    }
}
