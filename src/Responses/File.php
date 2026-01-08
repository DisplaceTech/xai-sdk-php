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
 * Represents an uploaded file.
 */
readonly class File
{
    /**
     * File purposes.
     */
    public const PURPOSE_ASSISTANTS = 'assistants';

    public const PURPOSE_FINE_TUNE = 'fine-tune';

    public const PURPOSE_BATCH = 'batch';

    /**
     * Creates a new File instance.
     *
     * @param string $id The unique file identifier.
     * @param string $filename The original filename.
     * @param int $size The file size in bytes.
     * @param string $contentType The MIME content type.
     * @param string $purpose The file purpose (assistants, fine-tune, batch).
     * @param DateTimeImmutable $createdAt When the file was created.
     * @param DateTimeImmutable|null $expiresAt When the file expires.
     * @param string|null $teamId The team ID that owns this file.
     * @param string $status The file status (uploaded, processed, error).
     * @param string|null $statusDetails Additional status details.
     */
    public function __construct(
        public string $id,
        public string $filename,
        public int $size,
        public string $contentType = 'application/octet-stream',
        public string $purpose = self::PURPOSE_ASSISTANTS,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        public ?DateTimeImmutable $expiresAt = null,
        public ?string $teamId = null,
        public string $status = 'uploaded',
        public ?string $statusDetails = null,
    ) {
    }

    /**
     * Creates a File from an API response array.
     *
     * @param array<string, mixed> $data The file data.
     *
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $createdAt = new DateTimeImmutable();

        if (isset($data['created_at'])) {
            $createdAt = is_int($data['created_at'])
                ? $createdAt->setTimestamp($data['created_at'])
                : new DateTimeImmutable($data['created_at']);
        } elseif (isset($data['created'])) {
            $createdAt = $createdAt->setTimestamp($data['created']);
        }

        $expiresAt = null;

        if (isset($data['expires_at'])) {
            $expiresAt = is_int($data['expires_at'])
                ? new DateTimeImmutable()->setTimestamp($data['expires_at'])
                : new DateTimeImmutable($data['expires_at']);
        }

        return new self(
            id: $data['id'] ?? $data['file_id'] ?? '',
            filename: $data['filename'] ?? $data['name'] ?? '',
            size: $data['bytes'] ?? $data['size'] ?? $data['size_bytes'] ?? 0,
            contentType: $data['content_type'] ?? $data['mime_type'] ?? 'application/octet-stream',
            purpose: $data['purpose'] ?? self::PURPOSE_ASSISTANTS,
            createdAt: $createdAt,
            expiresAt: $expiresAt,
            teamId: $data['team_id'] ?? null,
            status: $data['status'] ?? 'uploaded',
            statusDetails: $data['status_details'] ?? null,
        );
    }

    /**
     * Gets the file size in a human-readable format.
     */
    public function getHumanReadableSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return sprintf('%.2f %s', $size, $units[$unitIndex]);
    }

    /**
     * Checks if the file has been processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Checks if the file has an error.
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }
}
