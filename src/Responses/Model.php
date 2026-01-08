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
 * Represents an xAI model with its properties and pricing.
 */
readonly class Model
{
    /**
     * Model types.
     */
    public const TYPE_LANGUAGE = 'language';
    public const TYPE_EMBEDDING = 'embedding';
    public const TYPE_IMAGE = 'image';

    /**
     * Creates a new Model instance.
     *
     * @param string $id The unique identifier for the model.
     * @param string $name The display name of the model.
     * @param array<string> $aliases Alternative names for the model.
     * @param string $version The model version.
     * @param string $type The model type (language, embedding, image).
     * @param array<string> $inputModalities Supported input modalities.
     * @param array<string> $outputModalities Supported output modalities.
     * @param int $maxPromptLength Maximum prompt length in tokens.
     * @param DateTimeImmutable $created When the model was created.
     * @param string|null $systemFingerprint System fingerprint for the model.
     * @param float|null $promptTextTokenPrice Price per prompt text token.
     * @param float|null $promptImageTokenPrice Price per prompt image token.
     * @param float|null $cachedPromptTokenPrice Price per cached prompt token.
     * @param float|null $completionTextTokenPrice Price per completion text token.
     * @param float|null $searchPrice Price per search operation.
     * @param float|null $imagePrice Price per image (for image models).
     * @param string $ownedBy The organization that owns the model.
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $aliases = [],
        public string $version = '',
        public string $type = self::TYPE_LANGUAGE,
        public array $inputModalities = [],
        public array $outputModalities = [],
        public int $maxPromptLength = 0,
        public DateTimeImmutable $created = new DateTimeImmutable(),
        public ?string $systemFingerprint = null,
        public ?float $promptTextTokenPrice = null,
        public ?float $promptImageTokenPrice = null,
        public ?float $cachedPromptTokenPrice = null,
        public ?float $completionTextTokenPrice = null,
        public ?float $searchPrice = null,
        public ?float $imagePrice = null,
        public string $ownedBy = 'xai',
    ) {}

    /**
     * Checks if this is a language model.
     */
    public function isLanguageModel(): bool
    {
        return $this->type === self::TYPE_LANGUAGE;
    }

    /**
     * Checks if this is an embedding model.
     */
    public function isEmbeddingModel(): bool
    {
        return $this->type === self::TYPE_EMBEDDING;
    }

    /**
     * Checks if this is an image model.
     */
    public function isImageModel(): bool
    {
        return $this->type === self::TYPE_IMAGE;
    }

    /**
     * Creates a Model from an API response array.
     *
     * @param array<string, mixed> $data The model data.
     * @param string $type The model type.
     * @return self
     */
    public static function fromArray(array $data, string $type = self::TYPE_LANGUAGE): self
    {
        $created = new DateTimeImmutable();
        if (isset($data['created'])) {
            $created = $created->setTimestamp($data['created']);
        }

        return new self(
            id: $data['id'] ?? $data['name'] ?? '',
            name: $data['name'] ?? $data['id'] ?? '',
            aliases: $data['aliases'] ?? [],
            version: $data['version'] ?? '',
            type: $type,
            inputModalities: $data['input_modalities'] ?? [],
            outputModalities: $data['output_modalities'] ?? [],
            maxPromptLength: $data['max_prompt_length'] ?? 0,
            created: $created,
            systemFingerprint: $data['system_fingerprint'] ?? null,
            promptTextTokenPrice: $data['prompt_text_token_price'] ?? null,
            promptImageTokenPrice: $data['prompt_image_token_price'] ?? null,
            cachedPromptTokenPrice: $data['cached_prompt_token_price'] ?? null,
            completionTextTokenPrice: $data['completion_text_token_price'] ?? null,
            searchPrice: $data['search_price'] ?? null,
            imagePrice: $data['image_price'] ?? null,
            ownedBy: $data['owned_by'] ?? 'xai',
        );
    }
}
