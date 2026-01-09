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

use stdClass;

/**
 * Server-side tool for code execution.
 *
 * This tool enables the model to execute code during conversation, allowing
 * it to run computations, test algorithms, or perform data analysis as part
 * of generating responses.
 *
 * @example
 * ```php
 * use Displace\XaiSdk\Tools\CodeExecution;
 *
 * $codeExec = new CodeExecution();
 *
 * $chat = $client->chat->create(
 *     model: 'grok-4-fast',
 *     tools: [$codeExec],
 * );
 * ```
 */
readonly class CodeExecution implements ServerSideTool
{
    public const TYPE = 'code_execution';

    /**
     * Creates a new CodeExecution instance.
     */
    public function __construct()
    {
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
        return [
            self::TYPE => new stdClass(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function toResponsesArray(): array
    {
        return [
            'type' => self::TYPE,
        ];
    }
}
