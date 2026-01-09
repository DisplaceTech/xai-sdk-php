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

namespace Displace\XaiSdk\Resources;

use Displace\XaiSdk\Embeddings\EmbeddingsResponse;
use Displace\XaiSdk\Http\HttpClient;

/**
 * Resource for the embeddings API.
 *
 * This class provides access to the embeddings endpoint, allowing you to
 * generate vector embeddings for text inputs. These embeddings can be used
 * for semantic similarity, search, clustering, and other ML applications.
 *
 * @see https://docs.x.ai/docs/guides/embeddings
 */
class EmbeddingsResource
{
    private const ENDPOINT = '/embeddings';

    /**
     * Creates a new EmbeddingsResource instance.
     *
     * @param HttpClient $httpClient The HTTP client for making requests.
     */
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {
    }

    /**
     * Creates embeddings for the given input text(s).
     *
     * @param string $model The model to use for embeddings (e.g., 'v1').
     * @param string|array<string> $input The text(s) to embed. Can be a single string or array of strings.
     * @param string|null $encodingFormat The format for the embedding values ('float' or 'base64').
     * @param int|null $dimensions The number of dimensions for the embedding (if supported by the model).
     *
     * @return EmbeddingsResponse The embeddings response containing the vectors.
     */
    public function create(
        string $model,
        string|array $input,
        ?string $encodingFormat = null,
        ?int $dimensions = null,
    ): EmbeddingsResponse {
        $payload = $this->buildPayload($model, $input, $encodingFormat, $dimensions);

        $response = $this->httpClient->post(self::ENDPOINT, $payload);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return EmbeddingsResponse::fromArray($data);
    }

    /**
     * Builds the request payload for the embeddings endpoint.
     *
     * @param string $model The model to use.
     * @param string|array<string> $input The text(s) to embed.
     * @param string|null $encodingFormat The encoding format.
     * @param int|null $dimensions The number of dimensions.
     *
     * @return array<string, mixed> The request payload.
     */
    private function buildPayload(
        string $model,
        string|array $input,
        ?string $encodingFormat = null,
        ?int $dimensions = null,
    ): array {
        $payload = [
            'model' => $model,
            'input' => $input,
        ];

        if ($encodingFormat !== null) {
            $payload['encoding_format'] = $encodingFormat;
        }

        if ($dimensions !== null) {
            $payload['dimensions'] = $dimensions;
        }

        return $payload;
    }
}
