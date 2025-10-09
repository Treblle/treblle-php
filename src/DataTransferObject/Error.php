<?php

declare(strict_types=1);

namespace Treblle\Php\DataTransferObject;

use JsonSerializable;

/**
 * Represents an error or exception that occurred during request processing.
 *
 * This DTO captures detailed information about PHP errors, exceptions, and
 * shutdown errors, including their source, type, message, and location.
 *
 * @package Treblle\Php\DataTransferObject
 */
final readonly class Error implements JsonSerializable
{
    /**
     * Constructs a new Error object.
     *
     * @param string $message The error message
     * @param string $file The file path where the error occurred
     * @param int $line The line number where the error occurred
     * @param string $source The error source (onError, onException, or onShutdown)
     * @param string $type The error type (e.g., UNHANDLED_EXCEPTION, E_ERROR, etc.)
     */
    public function __construct(
        private string $message,
        private string $file,
        private int    $line,
        private string $source = 'onError',
        private string $type = 'UNHANDLED_EXCEPTION',
    ) {
    }

    /**
     * Gets the error source.
     *
     * Indicates how the error was captured in PHP. Possible values:
     * - onError: Error captured via set_error_handler()
     * - onException: Exception captured via set_exception_handler()
     * - onShutdown: Fatal error captured via register_shutdown_function()
     *
     * @return string The error source (onError, onException, or onShutdown)
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Gets the error type.
     *
     * For PHP, this typically includes error constants like E_ERROR, E_WARNING,
     * E_NOTICE, or exception class names. Defaults to UNHANDLED_EXCEPTION if
     * the specific type cannot be determined.
     *
     * @return string The error type identifier
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Gets the error message.
     *
     * @return string The error message text
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Gets the file path where the error occurred.
     *
     * @return string The absolute or relative file path
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Gets the line number where the error occurred.
     *
     * @return int The line number in the file
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * Serializes the Error object to JSON format.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
