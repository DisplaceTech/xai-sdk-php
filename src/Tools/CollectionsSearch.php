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

namespace Displace\XaiSdk\Tools;

use InvalidArgumentException;
use stdClass;

/**
 * Server-side tool for collections search (RAG).
 *
 * This tool enables the model to search collections and access document
 * content during conversation.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Tools\CollectionsSearch;
 *
 * $collectionsSearch = new CollectionsSearch(
 *     collectionIds: ['collection-1', 'collection-2'],
 *     limit: 10,
 *     retrievalMode: 'semantic',
 * );
 * ```
 */
readonly class CollectionsSearch implements ServerSideTool
{
    public const TYPE = 'collections_search';

    public const RETRIEVAL_HYBRID = 'hybrid';

    public const RETRIEVAL_SEMANTIC = 'semantic';

    public const RETRIEVAL_KEYWORD = 'keyword';

    /**
     * Creates a new CollectionsSearch instance.
     *
     * @param array<string> $collectionIds The IDs of collections to search (max 10).
     * @param int|null $limit Maximum number of results to return. Defaults to 10.
     * @param string|null $instructions Custom instructions for search interpretation.
     * @param string|null $retrievalMode Retrieval strategy: 'hybrid', 'semantic', or 'keyword'.
     */
    public function __construct(
        public array $collectionIds,
        public ?int $limit = null,
        public ?string $instructions = null,
        public ?string $retrievalMode = null,
    ) {
        if ($retrievalMode !== null && ! in_array($retrievalMode, [
            self::RETRIEVAL_HYBRID,
            self::RETRIEVAL_SEMANTIC,
            self::RETRIEVAL_KEYWORD,
        ], true)) {
            throw new InvalidArgumentException(
                "Invalid retrievalMode '{$retrievalMode}'. Must be one of: hybrid, semantic, keyword.",
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $config = [
            'collection_ids' => $this->collectionIds,
        ];

        if ($this->limit !== null) {
            $config['limit'] = $this->limit;
        }

        if ($this->instructions !== null) {
            $config['instructions'] = $this->instructions;
        }

        if ($this->retrievalMode !== null) {
            $config["{$this->retrievalMode}_retrieval"] = new stdClass();
        }

        return [
            self::TYPE => $config,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function toResponsesArray(): array
    {
        $result = [
            'type' => self::TYPE,
            'collection_ids' => $this->collectionIds,
        ];

        if ($this->limit !== null) {
            $result['limit'] = $this->limit;
        }

        if ($this->instructions !== null) {
            $result['instructions'] = $this->instructions;
        }

        if ($this->retrievalMode !== null) {
            $result['retrieval_mode'] = $this->retrievalMode;
        }

        return $result;
    }
}
