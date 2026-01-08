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

use Displace\XaiSdk\Http\HttpClient;
use Displace\XaiSdk\Responses\ImageResponse;

/**
 * Resource for image generation API.
 *
 * This class provides access to the image generation endpoint, allowing you
 * to generate images from text prompts using xAI's image models.
 *
 * @example
 * ```php
 * // Generate a single image
 * $image = $client->image->generate(
 *     prompt: 'A beautiful sunset over mountains',
 *     model: 'grok-2-image'
 * );
 * $image->saveToFile('sunset.jpg');
 *
 * // Generate multiple images
 * $images = $client->image->generateBatch(
 *     prompt: 'A futuristic city',
 *     n: 4,
 *     model: 'grok-2-image'
 * );
 * ```
 */
class ImageResource
{
    /**
     * Image format options.
     */
    public const FORMAT_URL = 'url';

    public const FORMAT_BASE64 = 'b64_json';

    private const ENDPOINT = '/images/generations';

    /**
     * Creates a new ImageResource instance.
     *
     * @param HttpClient $httpClient The HTTP client for making requests.
     */
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {
    }

    /**
     * Generates a single image from a prompt.
     *
     * @param string $prompt The text prompt describing the image to generate.
     * @param string $model The image model to use (e.g., 'grok-2-image').
     * @param string $responseFormat The format for the response ('url' or 'b64_json').
     * @param string|null $size The image size (e.g., '1024x1024').
     * @param string|null $quality The image quality ('standard' or 'hd').
     * @param string|null $style The image style ('vivid' or 'natural').
     * @param string|null $user A unique identifier for the end-user.
     *
     * @return ImageResponse The generated image.
     */
    public function generate(
        string $prompt,
        string $model = 'grok-2-image',
        string $responseFormat = self::FORMAT_URL,
        ?string $size = null,
        ?string $quality = null,
        ?string $style = null,
        ?string $user = null,
    ): ImageResponse {
        $images = $this->generateBatch(
            prompt: $prompt,
            n: 1,
            model: $model,
            responseFormat: $responseFormat,
            size: $size,
            quality: $quality,
            style: $style,
            user: $user,
        );

        return $images[0];
    }

    /**
     * Generates multiple images from a prompt.
     *
     * @param string $prompt The text prompt describing the images to generate.
     * @param int $n The number of images to generate (1-10).
     * @param string $model The image model to use (e.g., 'grok-2-image').
     * @param string $responseFormat The format for the response ('url' or 'b64_json').
     * @param string|null $size The image size (e.g., '1024x1024').
     * @param string|null $quality The image quality ('standard' or 'hd').
     * @param string|null $style The image style ('vivid' or 'natural').
     * @param string|null $user A unique identifier for the end-user.
     *
     * @return array<ImageResponse> The generated images.
     */
    public function generateBatch(
        string $prompt,
        int $n = 1,
        string $model = 'grok-2-image',
        string $responseFormat = self::FORMAT_URL,
        ?string $size = null,
        ?string $quality = null,
        ?string $style = null,
        ?string $user = null,
    ): array {
        $payload = [
            'prompt' => $prompt,
            'model' => $model,
            'n' => $n,
            'response_format' => $responseFormat,
        ];

        if ($size !== null) {
            $payload['size'] = $size;
        }

        if ($quality !== null) {
            $payload['quality'] = $quality;
        }

        if ($style !== null) {
            $payload['style'] = $style;
        }

        if ($user !== null) {
            $payload['user'] = $user;
        }

        $response = $this->httpClient->post(self::ENDPOINT, $payload);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $created = $data['created'] ?? time();

        return array_map(
            fn (array $imageData) => ImageResponse::fromArray($imageData, $created),
            $data['data'] ?? [],
        );
    }

    /**
     * Alias for generate() to match Python SDK naming.
     *
     * @param string $prompt The text prompt.
     * @param string $model The image model to use.
     * @param string $imageFormat The response format ('url' or 'base64').
     *
     * @return ImageResponse
     */
    public function sample(
        string $prompt,
        string $model = 'grok-2-image',
        string $imageFormat = 'url',
    ): ImageResponse {
        $format = $imageFormat === 'base64' ? self::FORMAT_BASE64 : self::FORMAT_URL;

        return $this->generate($prompt, $model, $format);
    }

    /**
     * Alias for generateBatch() to match Python SDK naming.
     *
     * @param string $prompt The text prompt.
     * @param int $n Number of images to generate.
     * @param string $model The image model to use.
     * @param string $imageFormat The response format ('url' or 'base64').
     *
     * @return array<ImageResponse>
     */
    public function sampleBatch(
        string $prompt,
        int $n = 1,
        string $model = 'grok-2-image',
        string $imageFormat = 'url',
    ): array {
        $format = $imageFormat === 'base64' ? self::FORMAT_BASE64 : self::FORMAT_URL;

        return $this->generateBatch($prompt, $n, $model, $format);
    }
}
