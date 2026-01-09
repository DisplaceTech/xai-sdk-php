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

/**
 * Response from the /v1/responses endpoint.
 *
 * This response format differs from /chat/completions:
 * - Uses 'output' array instead of 'choices'
 * - Content is in output_text blocks
 * - Tool calls appear as custom_tool_call entries
 */
readonly class ResponsesResponse
{
    /**
     * Creates a new ResponsesResponse instance.
     *
     * @param string $id The response ID.
     * @param string $status The response status (e.g., 'completed').
     * @param array<OutputItem> $output The output items.
     * @param string|null $model The model used.
     * @param Usage|null $usage Token usage statistics.
     * @param array<string, mixed> $rawData The raw response data.
     */
    public function __construct(
        public string $id,
        public string $status,
        public array $output,
        public ?string $model = null,
        public ?Usage $usage = null,
        public array $rawData = [],
    ) {
    }

    /**
     * Creates a ResponsesResponse from an array.
     *
     * @param array<string, mixed> $data The response data.
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $output = [];

        foreach ($data['output'] ?? [] as $item) {
            $output[] = OutputItem::fromArray($item);
        }

        $usage = null;

        if (isset($data['usage'])) {
            $usage = Usage::fromArray($data['usage']);
        }

        return new self(
            id: $data['id'] ?? '',
            status: $data['status'] ?? 'unknown',
            output: $output,
            model: $data['model'] ?? null,
            usage: $usage,
            rawData: $data,
        );
    }

    /**
     * Gets the text content from the response.
     *
     * Extracts text from all output_text blocks in message outputs.
     *
     * @return string The combined text content.
     */
    public function getContent(): string
    {
        $content = '';

        foreach ($this->output as $item) {
            if ($item->type === 'message') {
                $content .= $item->getText();
            }
        }

        return $content;
    }

    /**
     * Gets all tool calls from the response.
     *
     * @return array<OutputItem> Tool call items.
     */
    public function getToolCalls(): array
    {
        return array_filter(
            $this->output,
            fn (OutputItem $item) => $item->type === 'custom_tool_call',
        );
    }

    /**
     * Checks if the response completed successfully.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
