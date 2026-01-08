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

namespace Displace\XaiSdk\Responses;

use DateTimeImmutable;

/**
 * Response from an image generation request.
 */
readonly class ImageResponse
{
    /**
     * Creates a new image response.
     *
     * @param string $prompt The actual prompt used to generate the image (may be rewritten by the system).
     * @param string|null $url The URL where the image is stored (if format is 'url').
     * @param string|null $base64 The base64-encoded image data (if format is 'base64').
     * @param string $revisedPrompt The revised/upsampled prompt used for generation.
     * @param DateTimeImmutable $created When the image was created.
     */
    public function __construct(
        public string $prompt,
        public ?string $url = null,
        public ?string $base64 = null,
        public string $revisedPrompt = '',
        public DateTimeImmutable $created = new DateTimeImmutable(),
    ) {}

    /**
     * Gets the image data as raw bytes.
     *
     * If the image was returned as base64, this decodes it.
     * If the image was returned as URL, this fetches it.
     *
     * @return string The raw image bytes.
     * @throws \RuntimeException If unable to get image data.
     */
    public function getImageData(): string
    {
        if ($this->base64 !== null) {
            return $this->decodeBase64();
        }

        if ($this->url !== null) {
            $data = file_get_contents($this->url);
            if ($data === false) {
                throw new \RuntimeException('Failed to fetch image from URL: ' . $this->url);
            }
            return $data;
        }

        throw new \RuntimeException('No image data available.');
    }

    /**
     * Saves the image to a file.
     *
     * @param string $path The file path to save to.
     * @return int The number of bytes written.
     * @throws \RuntimeException If unable to save the image.
     */
    public function saveToFile(string $path): int
    {
        $data = $this->getImageData();
        $result = file_put_contents($path, $data);

        if ($result === false) {
            throw new \RuntimeException('Failed to save image to: ' . $path);
        }

        return $result;
    }

    /**
     * Decodes the base64 image data.
     *
     * @return string The raw image bytes.
     * @throws \RuntimeException If base64 data is not available.
     */
    private function decodeBase64(): string
    {
        if ($this->base64 === null) {
            throw new \RuntimeException('No base64 data available.');
        }

        // Handle data URI prefix if present
        $data = $this->base64;
        if (str_contains($data, 'base64,')) {
            $data = explode('base64,', $data, 2)[1];
        }

        $decoded = base64_decode($data, strict: true);
        if ($decoded === false) {
            throw new \RuntimeException('Failed to decode base64 image data.');
        }

        return $decoded;
    }

    /**
     * Creates an ImageResponse from an API response array.
     *
     * @param array<string, mixed> $data The response data for a single image.
     * @param int $created The creation timestamp.
     * @return self
     */
    public static function fromArray(array $data, int $created = 0): self
    {
        return new self(
            prompt: $data['revised_prompt'] ?? $data['prompt'] ?? '',
            url: $data['url'] ?? null,
            base64: $data['b64_json'] ?? null,
            revisedPrompt: $data['revised_prompt'] ?? '',
            created: (new DateTimeImmutable())->setTimestamp($created !== 0 ? $created : time()),
        );
    }
}
