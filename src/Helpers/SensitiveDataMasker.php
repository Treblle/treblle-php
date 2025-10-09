<?php

declare(strict_types=1);

namespace Treblle\Php\Helpers;

/**
 * Masks sensitive data in request/response payloads.
 *
 * This helper class provides comprehensive data masking functionality to ensure
 * sensitive information is protected before being sent to Treblle. It handles
 * various types of sensitive data including passwords, credit card numbers,
 * API keys, authorization tokens, and large base64-encoded images.
 *
 * The masker uses multiple strategies:
 * - Field name matching (case-insensitive)
 * - Automatic detection of authorization headers
 * - Detection of base64-encoded images
 * - Recursive masking for nested arrays
 *
 * @package Treblle\Php\Helpers
 */
final class SensitiveDataMasker
{
    /**
     * Constructs a new SensitiveDataMasker.
     *
     * The field names provided are matched case-insensitively against
     * keys in the data being masked. Static caching is used for performance
     * when processing large datasets.
     *
     * @param list<string> $fields List of field names to mask (e.g., 'password', 'api_key')
     */
    public function __construct(
        public array $fields = [],
    ) {
    }

    /**
     * Recursively masks sensitive data in the provided array.
     *
     * Processes each element in the array:
     * - Arrays: Recursively masks nested data
     * - Strings: Checks against masked fields, sensitive headers, and base64 images
     * - Other types: Returns unchanged
     *
     * Example:
     * ```php
     * $masker = new SensitiveDataMasker(['password', 'secret']);
     * $data = [
     *     'username' => 'john',
     *     'password' => 'secret123',
     *     'profile' => ['secret' => 'hidden']
     * ];
     * $masked = $masker->mask($data);
     * // Result: ['username' => 'john', 'password' => '*********', 'profile' => ['secret' => '******']]
     * ```
     *
     * @param array<int|string, mixed> $data The data to mask
     * @return array<int|string, mixed> The masked data with sensitive values replaced
     */
    public function mask(array $data): array
    {
        $collector = [];
        foreach ($data as $key => $value) {
            $collector[$key] = match (true) {
                is_array($value) => $this->mask(
                    data: $value,
                ),
                is_string($value) => $this->handleString(
                    key: $key,
                    value: $value,
                ),
                default => $value,
            };
        }

        return $collector;
    }

    /**
     * Replaces a string with asterisks of the same length.
     *
     * Uses multibyte string length to correctly handle Unicode characters.
     * This ensures that masked values maintain the same visual length as
     * the original, which can be useful for debugging while maintaining security.
     *
     * Example:
     * ```php
     * $masker->star('password123'); // Returns: ***********
     * $masker->star('café');        // Returns: ****
     * ```
     *
     * @param string $string The string to replace with asterisks
     * @return string A string of asterisks with the same length as the input
     */
    public function star(string $string): string
    {
        return str_repeat('*', mb_strlen($string));
    }

    /**
     * Handles masking of string values based on field name and content.
     *
     * Applies masking logic in the following order:
     * 1. Checks if field name matches configured sensitive fields (case-insensitive)
     * 2. Checks if field is a sensitive header (authorization, x-api-key)
     * 3. Checks if value is a base64-encoded image
     * 4. Returns original value if no masking rules match
     *
     * Uses static caching for lowercase field names to optimize performance
     * when processing multiple values.
     *
     * @param bool|float|int|string $key The field name/key (will be converted to string if needed)
     * @param string $value The field value to potentially mask
     * @return string The masked value or original value if no masking needed
     */
    private function handleString(bool|float|int|string $key, string $value): string
    {
        if (! is_string($key)) {
            $key = (string) $key;
        }

        static $lowerFields = null;
        if (null === $lowerFields) {
            $lowerFields = array_map('strtolower', $this->fields);
        }

        $lowerKey = mb_strtolower($key);

        if (in_array($lowerKey, $lowerFields, true)) {
            return $this->star($value);
        }

        if ($this->isSensitiveHeader($lowerKey)) {
            return $this->maskAuthorization($value);
        }

        if ($this->isBase64($value)) {
            return 'base64 encoded images are too big to process';
        }

        return $value;
    }

    /**
     * Masks authorization header values while preserving the auth type.
     *
     * For recognized authorization schemes (Bearer, Basic, Digest), masks only
     * the credential portion while keeping the scheme visible. This allows
     * debugging of auth type issues while protecting the actual credentials.
     *
     * Examples:
     * ```php
     * maskAuthorization('Bearer eyJhbGc...') // Returns: 'Bearer **********'
     * maskAuthorization('Basic dXNlcjp...') // Returns: 'Basic **********'
     * maskAuthorization('CustomAuth xyz')   // Returns: '***************'
     * ```
     *
     * @param string $value The authorization header value
     * @return string The masked authorization value with scheme preserved (if recognized)
     */
    private function maskAuthorization(string $value): string
    {
        $parts = explode(' ', $value, 2);
        if (isset($parts[1])) {
            $authTypeLower = mb_strtolower($parts[0]);
            if (in_array($authTypeLower, ['bearer', 'basic', 'digest'], true)) {
                return $parts[0] . ' ' . $this->star($parts[1]);
            }
        }

        return $this->star($value);
    }

    /**
     * Checks if a key represents a sensitive header.
     *
     * Currently detects:
     * - authorization: OAuth tokens, Basic auth, etc.
     * - x-api-key: Common API key header
     *
     * These headers are automatically masked regardless of the configured
     * field list to ensure common authentication credentials are protected.
     *
     * @param string $key The header key in lowercase
     * @return bool True if the header contains sensitive authentication data
     */
    private function isSensitiveHeader(string $key): bool
    {
        return in_array($key, ['authorization', 'x-api-key'], true);
    }

    /**
     * Checks if a string is a base64-encoded image.
     *
     * Detects data URIs for images to avoid sending large base64-encoded
     * images to Treblle, which can significantly increase payload size and
     * provide little debugging value.
     *
     * Example detected format: "data:image/png;base64,iVBORw0KGgoAAAANSU..."
     *
     * @param string $string The string to check
     * @return bool True if it matches the base64 image data URI pattern
     */
    private function isBase64(string $string): bool
    {
        return str_starts_with($string, 'data:image/') && str_contains($string, ';base64,');
    }
}
