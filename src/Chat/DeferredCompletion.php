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

namespace Displace\XaiSdk\Chat;

use DateTimeImmutable;
use Displace\XaiSdk\Responses\ChatResponse;

/**
 * Represents a deferred completion request.
 *
 * When a chat completion request takes longer than expected, the API may
 * return a deferred completion that can be polled for the final result.
 * This is useful for long-running requests that might otherwise timeout.
 */
readonly class DeferredCompletion
{
    /**
     * Status indicating the request is still being processed.
     */
    public const STATUS_PENDING = 'pending';

    /**
     * Status indicating the request is actively being processed.
     */
    public const STATUS_PROCESSING = 'processing';

    /**
     * Status indicating the request completed successfully.
     */
    public const STATUS_COMPLETED = 'completed';

    /**
     * Status indicating the request failed.
     */
    public const STATUS_FAILED = 'failed';

    /**
     * Status indicating the request was cancelled.
     */
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Creates a new DeferredCompletion instance.
     *
     * @param string $id Unique identifier for the deferred completion.
     * @param string $status Current status of the request.
     * @param DateTimeImmutable $createdAt When the request was created.
     * @param DateTimeImmutable|null $completedAt When the request completed (if applicable).
     * @param ChatResponse|null $result The completion result (when status is completed).
     * @param string|null $error Error message (when status is failed).
     * @param string $object The object type (always "deferred_completion").
     */
    public function __construct(
        public string $id,
        public string $status,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $completedAt = null,
        public ?ChatResponse $result = null,
        public ?string $error = null,
        public string $object = 'deferred_completion',
    ) {
    }

    /**
     * Creates a DeferredCompletion from an API response array.
     *
     * @param array<string, mixed> $data The raw API response data.
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $createdAt = isset($data['created_at'])
            ? new DateTimeImmutable('@' . $data['created_at'])
            : new DateTimeImmutable();

        $completedAt = isset($data['completed_at'])
            ? new DateTimeImmutable('@' . $data['completed_at'])
            : null;

        $result = isset($data['result'])
            ? ChatResponse::fromArray($data['result'])
            : null;

        return new self(
            id: $data['id'] ?? '',
            status: $data['status'] ?? self::STATUS_PENDING,
            createdAt: $createdAt,
            completedAt: $completedAt,
            result: $result,
            error: $data['error'] ?? null,
            object: $data['object'] ?? 'deferred_completion',
        );
    }

    /**
     * Check if the deferred completion is still pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING
            || $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if the deferred completion has completed successfully.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the deferred completion has failed.
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if the deferred completion was cancelled.
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if the deferred completion is finished (completed, failed, or cancelled).
     *
     * @return bool
     */
    public function isFinished(): bool
    {
        return $this->isCompleted() || $this->isFailed() || $this->isCancelled();
    }

    /**
     * Get the completion result.
     *
     * @return ChatResponse|null The result if completed, null otherwise.
     */
    public function getResult(): ?ChatResponse
    {
        return $this->result;
    }

    /**
     * Get the error message.
     *
     * @return string|null The error message if failed, null otherwise.
     */
    public function getError(): ?string
    {
        return $this->error;
    }
}
