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

namespace Displace\XaiSdk\Exceptions;

use Throwable;

/**
 * Exception thrown when invalid arguments are provided to SDK methods.
 *
 * This exception is thrown for client-side validation errors before any
 * API request is made. For server-side validation errors (400 Bad Request),
 * see ApiException.
 */
class InvalidArgumentException extends XaiException
{
    /**
     * Creates a new InvalidArgumentException instance.
     *
     * @param string $message The exception message
     * @param string|null $parameter The name of the invalid parameter
     * @param Throwable|null $previous The previous exception for chaining
     */
    public function __construct(
        string $message,
        public readonly ?string $parameter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Creates an exception for a missing required parameter.
     *
     * @param string $parameter The name of the missing parameter
     * @return self
     */
    public static function missingRequired(string $parameter): self
    {
        return new self(
            sprintf('The "%s" parameter is required but was not provided.', $parameter),
            $parameter,
        );
    }

    /**
     * Creates an exception for an invalid parameter type.
     *
     * @param string $parameter The name of the parameter
     * @param string $expected The expected type
     * @param string $actual The actual type provided
     * @return self
     */
    public static function invalidType(string $parameter, string $expected, string $actual): self
    {
        return new self(
            sprintf(
                'The "%s" parameter must be of type %s, %s given.',
                $parameter,
                $expected,
                $actual,
            ),
            $parameter,
        );
    }

    /**
     * Creates an exception for an invalid parameter value.
     *
     * @param string $parameter The name of the parameter
     * @param string $reason The reason the value is invalid
     * @return self
     */
    public static function invalidValue(string $parameter, string $reason): self
    {
        return new self(
            sprintf('Invalid value for "%s": %s', $parameter, $reason),
            $parameter,
        );
    }

    /**
     * Creates an exception for empty required value.
     *
     * @param string $parameter The name of the parameter
     * @return self
     */
    public static function emptyValue(string $parameter): self
    {
        return new self(
            sprintf('The "%s" parameter cannot be empty.', $parameter),
            $parameter,
        );
    }
}
