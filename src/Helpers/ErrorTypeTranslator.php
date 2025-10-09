<?php

declare(strict_types=1);

namespace Treblle\Php\Helpers;

/**
 * Translates PHP error type integers to their string constant names.
 *
 * This helper class converts PHP's error type constants (like E_ERROR, E_WARNING, etc.)
 * from their integer values to human-readable string representations. This is useful
 * for logging and sending error information to Treblle.
 *
 * @package Treblle\Php\Helpers
 */
final class ErrorTypeTranslator
{
    /**
     * Translates a PHP error type integer to its constant name.
     *
     * Converts error type constants to their string equivalents:
     * - Fatal errors: E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR
     * - Warnings: E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING
     * - Notices: E_NOTICE, E_USER_NOTICE
     * - Deprecation: E_DEPRECATED, E_USER_DEPRECATED
     * - Other: E_PARSE, E_STRICT, E_RECOVERABLE_ERROR
     *
     * @param int $type The PHP error type constant (e.g., E_ERROR, E_WARNING)
     * @return string The error type name or "Unknown: {type}" if not recognized
     */
    public static function translateErrorType(int $type): string
    {
        return match ($type) {
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED',
            default => 'Unknown: ' . (string)$type,
        };
    }
}
