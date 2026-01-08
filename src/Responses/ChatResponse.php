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
use Displace\XaiSdk\Chat\Messages\AssistantMessage;
use Displace\XaiSdk\Chat\Messages\Message;

/**
 * Response from a chat completion request.
 */
readonly class ChatResponse
{
    /**
     * Creates a new chat response.
     *
     * @param string $id Unique identifier for the response.
     * @param string $model The model used for generation.
     * @param DateTimeImmutable $created When the response was created.
     * @param array<Choice> $choices The generated completions.
     * @param Usage $usage Token usage statistics.
     * @param string|null $systemFingerprint System fingerprint for the response.
     */
    public function __construct(
        public string $id,
        public string $model,
        public DateTimeImmutable $created,
        public array $choices,
        public Usage $usage,
        public ?string $systemFingerprint = null,
    ) {}

    /**
     * Gets the content of the first choice.
     */
    public function getContent(): string
    {
        return $this->choices[0]?->message->content ?? '';
    }

    /**
     * Gets the reasoning content of the first choice.
     */
    public function getReasoningContent(): ?string
    {
        if ($this->choices === []) {
            return null;
        }
        return $this->choices[0]->message->reasoningContent;
    }

    /**
     * Gets the tool calls from the first choice.
     *
     * @return array<array<string, mixed>>
     */
    public function getToolCalls(): array
    {
        if ($this->choices === []) {
            return [];
        }
        return $this->choices[0]->toolCalls;
    }

    /**
     * Gets the finish reason from the first choice.
     */
    public function getFinishReason(): ?string
    {
        if ($this->choices === []) {
            return null;
        }
        return $this->choices[0]->finishReason;
    }

    /**
     * Converts the first choice to a message for conversation continuity.
     */
    public function toMessage(): Message
    {
        $choice = $this->choices[0] ?? null;

        if ($choice === null) {
            return new AssistantMessage('');
        }

        return $choice->message;
    }

    /**
     * Creates a ChatResponse from an API response array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $choices = array_map(
            fn(array $choice) => Choice::fromArray($choice),
            $data['choices'] ?? []
        );

        return new self(
            id: $data['id'] ?? '',
            model: $data['model'] ?? '',
            created: (new DateTimeImmutable())->setTimestamp($data['created'] ?? time()),
            choices: $choices,
            usage: Usage::fromArray($data['usage'] ?? []),
            systemFingerprint: $data['system_fingerprint'] ?? null,
        );
    }
}
