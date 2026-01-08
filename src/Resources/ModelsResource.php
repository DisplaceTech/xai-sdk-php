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
use Displace\XaiSdk\Responses\Model;

/**
 * Resource for models API.
 *
 * This class provides access to the models endpoint, allowing you to list
 * available models and retrieve detailed information about specific models.
 *
 * @example
 * ```php
 * // List all models
 * $models = $client->models->list();
 * foreach ($models as $model) {
 *     echo $model->name . "\n";
 * }
 *
 * // Get a specific model
 * $model = $client->models->retrieve('grok-3');
 * echo $model->maxPromptLength;
 * ```
 */
class ModelsResource
{
    private const ENDPOINT = '/models';

    /**
     * Creates a new ModelsResource instance.
     *
     * @param HttpClient $httpClient The HTTP client for making requests.
     */
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {}

    /**
     * Lists all available models.
     *
     * @return array<Model> The list of available models.
     */
    public function list(): array
    {
        $response = $this->httpClient->get(self::ENDPOINT);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return array_map(
            fn(array $modelData) => Model::fromArray($modelData),
            $data['data'] ?? []
        );
    }

    /**
     * Retrieves a specific model by ID.
     *
     * @param string $modelId The model ID to retrieve.
     * @return Model The model information.
     */
    public function retrieve(string $modelId): Model
    {
        $response = $this->httpClient->get(self::ENDPOINT . '/' . $modelId);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return Model::fromArray($data);
    }

    /**
     * Lists all language models.
     *
     * Filters the model list to return only language models.
     *
     * @return array<Model> The list of language models.
     */
    public function listLanguageModels(): array
    {
        return array_filter(
            $this->list(),
            fn(Model $model) => $model->isLanguageModel()
        );
    }

    /**
     * Lists all embedding models.
     *
     * Filters the model list to return only embedding models.
     *
     * @return array<Model> The list of embedding models.
     */
    public function listEmbeddingModels(): array
    {
        return array_filter(
            $this->list(),
            fn(Model $model) => $model->isEmbeddingModel()
        );
    }

    /**
     * Lists all image generation models.
     *
     * Filters the model list to return only image generation models.
     *
     * @return array<Model> The list of image models.
     */
    public function listImageGenerationModels(): array
    {
        return array_filter(
            $this->list(),
            fn(Model $model) => $model->isImageModel()
        );
    }

    /**
     * Gets a specific language model by name.
     *
     * @param string $name The model name.
     * @return Model The model information.
     */
    public function getLanguageModel(string $name): Model
    {
        $model = $this->retrieve($name);

        // Override type to language if not set
        return new Model(
            id: $model->id,
            name: $model->name,
            aliases: $model->aliases,
            version: $model->version,
            type: Model::TYPE_LANGUAGE,
            inputModalities: $model->inputModalities,
            outputModalities: $model->outputModalities,
            maxPromptLength: $model->maxPromptLength,
            created: $model->created,
            systemFingerprint: $model->systemFingerprint,
            promptTextTokenPrice: $model->promptTextTokenPrice,
            promptImageTokenPrice: $model->promptImageTokenPrice,
            cachedPromptTokenPrice: $model->cachedPromptTokenPrice,
            completionTextTokenPrice: $model->completionTextTokenPrice,
            searchPrice: $model->searchPrice,
            imagePrice: $model->imagePrice,
            ownedBy: $model->ownedBy,
        );
    }

    /**
     * Gets a specific embedding model by name.
     *
     * @param string $name The model name.
     * @return Model The model information.
     */
    public function getEmbeddingModel(string $name): Model
    {
        $model = $this->retrieve($name);

        return new Model(
            id: $model->id,
            name: $model->name,
            aliases: $model->aliases,
            version: $model->version,
            type: Model::TYPE_EMBEDDING,
            inputModalities: $model->inputModalities,
            outputModalities: $model->outputModalities,
            maxPromptLength: $model->maxPromptLength,
            created: $model->created,
            systemFingerprint: $model->systemFingerprint,
            promptTextTokenPrice: $model->promptTextTokenPrice,
            promptImageTokenPrice: $model->promptImageTokenPrice,
            cachedPromptTokenPrice: $model->cachedPromptTokenPrice,
            completionTextTokenPrice: $model->completionTextTokenPrice,
            searchPrice: $model->searchPrice,
            imagePrice: $model->imagePrice,
            ownedBy: $model->ownedBy,
        );
    }

    /**
     * Gets a specific image generation model by name.
     *
     * @param string $name The model name.
     * @return Model The model information.
     */
    public function getImageGenerationModel(string $name): Model
    {
        $model = $this->retrieve($name);

        return new Model(
            id: $model->id,
            name: $model->name,
            aliases: $model->aliases,
            version: $model->version,
            type: Model::TYPE_IMAGE,
            inputModalities: $model->inputModalities,
            outputModalities: $model->outputModalities,
            maxPromptLength: $model->maxPromptLength,
            created: $model->created,
            systemFingerprint: $model->systemFingerprint,
            promptTextTokenPrice: $model->promptTextTokenPrice,
            promptImageTokenPrice: $model->promptImageTokenPrice,
            cachedPromptTokenPrice: $model->cachedPromptTokenPrice,
            completionTextTokenPrice: $model->completionTextTokenPrice,
            searchPrice: $model->searchPrice,
            imagePrice: $model->imagePrice,
            ownedBy: $model->ownedBy,
        );
    }
}
