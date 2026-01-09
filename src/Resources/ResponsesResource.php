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

use Displace\XaiSdk\Chat\Messages\Message;
use Displace\XaiSdk\Http\HttpClient;
use Displace\XaiSdk\Responses\ResponsesResponse;
use Displace\XaiSdk\Tools\ServerSideTool;

/**
 * Resource for the /v1/responses endpoint.
 *
 * This endpoint is required for server-side tools (x_search, web_search,
 * code_execution) which are not supported by /chat/completions.
 *
 * @see https://docs.x.ai/docs/guides/tools/search-tools
 */
class ResponsesResource
{
    private const ENDPOINT = '/responses';

    /**
     * Creates a new ResponsesResource instance.
     *
     * @param HttpClient $httpClient The HTTP client for making requests.
     */
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {
    }

    /**
     * Sends a request to the responses endpoint.
     *
     * @param string $model The model to use.
     * @param array<Message> $messages The conversation messages.
     * @param array<ServerSideTool> $tools Server-side tools to use.
     * @param array<string, mixed> $options Additional options.
     *
     * @return ResponsesResponse The parsed response.
     */
    public function create(
        string $model,
        array $messages,
        array $tools,
        array $options = [],
    ): ResponsesResponse {
        $payload = $this->buildPayload($model, $messages, $tools, $options);

        $response = $this->httpClient->post(self::ENDPOINT, $payload);
        $body = $response->getBody();
        $body->rewind();
        $data = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return ResponsesResponse::fromArray($data);
    }

    /**
     * Builds the request payload for the responses endpoint.
     *
     * @param string $model The model to use.
     * @param array<Message> $messages The conversation messages.
     * @param array<ServerSideTool> $tools Server-side tools to use.
     * @param array<string, mixed> $options Additional options.
     *
     * @return array<string, mixed> The request payload.
     */
    public function buildPayload(
        string $model,
        array $messages,
        array $tools,
        array $options = [],
    ): array {
        // The responses endpoint uses 'input' instead of 'messages'
        $payload = [
            'model' => $model,
            'input' => array_map(fn (Message $m) => $m->toArray(), $messages),
            'tools' => array_map(fn (ServerSideTool $t) => $t->toResponsesArray(), $tools),
        ];

        // Add optional parameters
        $optionalParams = [
            'temperature',
            'max_output_tokens',
            'top_p',
            'stream',
        ];

        foreach ($optionalParams as $param) {
            if (isset($options[$param])) {
                $payload[$param] = $options[$param];
            }
        }

        return $payload;
    }
}
