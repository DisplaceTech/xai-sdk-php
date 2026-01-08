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

use Displace\XaiSdk\Exceptions\InvalidArgumentException;
use Displace\XaiSdk\Http\HttpClient;
use Displace\XaiSdk\Responses\File;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;

/**
 * Resource for files API.
 *
 * This class provides access to the files endpoint, allowing you to upload,
 * download, list, and delete files.
 *
 * @example
 * ```php
 * // Upload a file from path
 * $file = $client->files->upload('/path/to/document.pdf');
 * echo "Uploaded: {$file->id}\n";
 *
 * // Upload from bytes
 * $file = $client->files->uploadBytes($data, 'data.txt');
 *
 * // List files
 * $files = $client->files->list(limit: 10);
 *
 * // Download file content
 * $content = $client->files->content($file->id);
 *
 * // Delete a file
 * $client->files->delete($file->id);
 * ```
 */
class FilesResource
{
    private const ENDPOINT = '/files';

    /**
     * Creates a new FilesResource instance.
     *
     * @param HttpClient $httpClient The HTTP client for making requests.
     */
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {}

    /**
     * Uploads a file from a path.
     *
     * @param string $filePath Path to the file to upload.
     * @param string|null $purpose The file purpose (assistants, fine-tune, batch).
     * @return File The uploaded file.
     * @throws InvalidArgumentException If the file doesn't exist.
     */
    public function upload(string $filePath, ?string $purpose = null): File
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File not found: {$filePath}");
        }

        $filename = basename($filePath);
        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new InvalidArgumentException("Failed to read file: {$filePath}");
        }

        return $this->uploadBytes($content, $filename, $purpose);
    }

    /**
     * Uploads a file from bytes.
     *
     * @param string $data The file content as bytes.
     * @param string $filename The filename to use.
     * @param string|null $purpose The file purpose (assistants, fine-tune, batch).
     * @return File The uploaded file.
     */
    public function uploadBytes(string $data, string $filename, ?string $purpose = null): File
    {
        $multipart = [
            [
                'name' => 'file',
                'contents' => $data,
                'filename' => $filename,
            ],
            [
                'name' => 'purpose',
                'contents' => $purpose ?? File::PURPOSE_ASSISTANTS,
            ],
        ];

        $boundary = bin2hex(random_bytes(16));
        $stream = new MultipartStream($multipart, $boundary);

        $response = $this->httpClient->post(
            self::ENDPOINT,
            [],
            [
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            ]
        );

        // For multipart, we need to use a custom request
        $body = $stream->getContents();

        // Re-create the request properly for multipart
        $uri = $this->httpClient->getBaseUrl() . self::ENDPOINT;

        // Use the Guzzle client directly for multipart
        $guzzle = new \GuzzleHttp\Client([
            'timeout' => 300,
            'http_errors' => false,
        ]);

        $response = $guzzle->post($uri, [
            'multipart' => $multipart,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getApiKeyFromClient(),
            ],
        ]);

        $body = $response->getBody();
        $body->rewind();
        $responseData = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return File::fromArray($responseData);
    }

    /**
     * Lists uploaded files.
     *
     * @param int $limit Maximum number of files to return.
     * @param string $order Sort order ('asc' or 'desc').
     * @param string $sortBy Field to sort by ('created_at', 'filename').
     * @param string|null $after Pagination cursor for next page.
     * @return array{data: array<File>, has_more: bool, pagination_token: string|null}
     */
    public function list(
        int $limit = 20,
        string $order = 'desc',
        string $sortBy = 'created_at',
        ?string $after = null,
    ): array {
        $query = [
            'limit' => $limit,
            'order' => $order,
            'sort_by' => $sortBy,
        ];

        if ($after !== null) {
            $query['after'] = $after;
        }

        $response = $this->httpClient->get(self::ENDPOINT, $query);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $files = array_map(
            fn(array $fileData) => File::fromArray($fileData),
            $data['data'] ?? []
        );

        return [
            'data' => $files,
            'has_more' => $data['has_more'] ?? false,
            'pagination_token' => $data['pagination_token'] ?? null,
        ];
    }

    /**
     * Gets file metadata by ID.
     *
     * @param string $fileId The file ID to retrieve.
     * @return File The file metadata.
     */
    public function retrieve(string $fileId): File
    {
        $response = $this->httpClient->get(self::ENDPOINT . '/' . $fileId);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return File::fromArray($data);
    }

    /**
     * Alias for retrieve() to match Python SDK naming.
     *
     * @param string $fileId The file ID to retrieve.
     * @return File The file metadata.
     */
    public function get(string $fileId): File
    {
        return $this->retrieve($fileId);
    }

    /**
     * Downloads file content.
     *
     * @param string $fileId The file ID to download.
     * @return string The file content as bytes.
     */
    public function content(string $fileId): string
    {
        $response = $this->httpClient->get(self::ENDPOINT . '/' . $fileId . '/content');
        $body = $response->getBody();
        $body->rewind();

        return $body->getContents();
    }

    /**
     * Downloads file content and saves to a local file.
     *
     * @param string $fileId The file ID to download.
     * @param string $destinationPath The local path to save the file.
     * @return int The number of bytes written.
     */
    public function download(string $fileId, string $destinationPath): int
    {
        $content = $this->content($fileId);
        $result = file_put_contents($destinationPath, $content);

        if ($result === false) {
            throw new \RuntimeException("Failed to write file to: {$destinationPath}");
        }

        return $result;
    }

    /**
     * Deletes a file.
     *
     * @param string $fileId The file ID to delete.
     * @return array{id: string, deleted: bool} Deletion result.
     */
    public function delete(string $fileId): array
    {
        $response = $this->httpClient->delete(self::ENDPOINT . '/' . $fileId);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return [
            'id' => $data['id'] ?? $fileId,
            'deleted' => $data['deleted'] ?? true,
        ];
    }

    /**
     * Gets the API key from the HTTP client (for multipart requests).
     *
     * @return string
     */
    private function getApiKeyFromClient(): string
    {
        // This is a workaround - ideally the HttpClient would expose this
        // For now, we'll use reflection or rely on environment variable
        $key = getenv('XAI_API_KEY');

        if ($key === false || $key === '') {
            throw new \RuntimeException('XAI_API_KEY environment variable not set');
        }

        return $key;
    }
}
