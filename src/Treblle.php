<?php

declare(strict_types=1);

namespace Treblle\Php;

use Throwable;
use GuzzleHttp\ClientInterface;
use Treblle\Php\DataTransferObject\Data;
use GuzzleHttp\Exception\GuzzleException;
use Treblle\Php\DataTransferObject\Error;
use Treblle\Php\Contract\ErrorDataProvider;
use Treblle\Php\Contract\ServerDataProvider;
use Treblle\Php\Helpers\ErrorTypeTranslator;
use Treblle\Php\Contract\RequestDataProvider;
use Treblle\Php\Contract\LanguageDataProvider;
use Treblle\Php\Contract\ResponseDataProvider;

/**
 * Core Treblle SDK class for capturing and transmitting API data.
 *
 * This is the main class of the Treblle PHP SDK. It captures HTTP request/response
 * data, PHP errors, and exceptions, then transmits them to the Treblle platform
 * for API monitoring, analytics, and documentation.
 *
 * Key responsibilities:
 * - Registers PHP error, exception, and shutdown handlers
 * - Captures errors and exceptions during request processing
 * - Collects server, language, request, response, and error data
 * - Builds JSON payload with masked sensitive data
 * - Transmits data to Treblle endpoints
 * - Supports optional background processing via pcntl_fork
 *
 * Usage:
 * Typically created via TreblleFactory rather than direct instantiation.
 * The factory registers handlers automatically on creation.
 *
 * Data Flow:
 * 1. Handlers registered: set_error_handler, set_exception_handler, register_shutdown_function
 * 2. During request: Errors/exceptions captured via onError/onException
 * 3. On shutdown: onShutdown builds payload from all providers and sends to Treblle
 *
 * Background Processing:
 * When fork_process is enabled and pcntl extension is available:
 * - Forks a child process to send data
 * - Parent process continues/exits immediately
 * - Child process sends data then terminates
 *
 * Debug Mode:
 * - OFF (default): Silently catches and ignores SDK errors
 * - ON: Throws exceptions for easier troubleshooting
 *
 * Get started at https://platform.treblle.com
 *
 * @package Treblle\Php
 */
final class Treblle
{
    /**
     * @var string SDK name identifier (default: 'php')
     */
    private string $name = 'php';

    /**
     * @var float SDK version number (default: 5.0)
     */
    private float $version = 5.0;

    /**
     * Constructs a new Treblle SDK instance.
     *
     * This constructor is typically not called directly. Use TreblleFactory::create()
     * instead, which provides sensible defaults and automatic handler registration.
     *
     * @param string $apiKey Your Treblle project API key
     * @param string $sdkToken Your Treblle SDK authentication token
     * @param ClientInterface $client HTTP client for transmitting data to Treblle
     * @param ServerDataProvider $serverDataProvider Provides server/OS information
     * @param LanguageDataProvider $languageDataProvider Provides PHP version info
     * @param RequestDataProvider $requestDataProvider Provides HTTP request data
     * @param ResponseDataProvider $responseDataProvider Provides HTTP response data
     * @param ErrorDataProvider $errorDataProvider Stores and provides error data
     * @param bool $debug Enable debug mode (throws exceptions instead of silent failures)
     * @param string|null $url Custom Treblle endpoint URL (uses random default if null)
     * @param bool $forkProcess Enable background processing via pcntl_fork
     */
    public function __construct(
        private readonly string      $apiKey,
        private readonly string      $sdkToken,
        private readonly ClientInterface      $client,
        private readonly ServerDataProvider   $serverDataProvider,
        private readonly LanguageDataProvider $languageDataProvider,
        private readonly RequestDataProvider  $requestDataProvider,
        private readonly ResponseDataProvider $responseDataProvider,
        private readonly ErrorDataProvider    $errorDataProvider,
        private readonly bool                 $debug,
        private readonly ?string              $url = null,
        private readonly bool                 $forkProcess = false
    ) {
    }

    /**
     * Captures PHP errors via set_error_handler callback.
     *
     * This method is registered as the global error handler using set_error_handler()
     * in TreblleFactory. It captures all PHP errors (warnings, notices, fatal errors, etc.)
     * and stores them in the error data provider for transmission to Treblle.
     *
     * The error type is translated from its integer constant (e.g., E_WARNING)
     * to a human-readable string (e.g., 'E_WARNING') using ErrorTypeTranslator.
     *
     * Error Handling:
     * - Debug OFF: Silently catches any exceptions during error capture
     * - Debug ON: Re-throws exceptions for troubleshooting
     *
     * @param int $type The error type constant (E_ERROR, E_WARNING, etc.)
     * @param string $message The error message
     * @param string $file The filename where the error occurred
     * @param int $line The line number where the error occurred
     * @return bool Always returns false to allow normal error handling to continue
     * @throws Throwable Only in debug mode if an exception occurs during error capture
     */
    public function onError(int $type, string $message, string $file, int $line): bool
    {
        try {
            $this->errorDataProvider->addError(new Error(
                $message,
                $file,
                $line,
                'onError',
                ErrorTypeTranslator::translateErrorType($type),
            ));
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }

        return false;
    }

    /**
     * Captures unhandled PHP exceptions via set_exception_handler callback.
     *
     * This method is registered as the global exception handler using set_exception_handler()
     * in TreblleFactory. It captures all unhandled exceptions that would otherwise
     * terminate the script and stores them for transmission to Treblle.
     *
     * Exception details captured:
     * - Message from $exception->getMessage()
     * - File path from $exception->getFile()
     * - Line number from $exception->getLine()
     * - Source marked as 'onException'
     * - Type marked as 'UNHANDLED_EXCEPTION'
     *
     * Error Handling:
     * - Debug OFF: Silently catches any exceptions during exception capture
     * - Debug ON: Re-throws exceptions for troubleshooting
     *
     * @param Throwable $exception The unhandled exception to capture
     * @return void
     * @throws Throwable Only in debug mode if an exception occurs during exception capture
     */
    public function onException(Throwable $exception): void
    {
        try {
            $this->errorDataProvider->addError(new Error(
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                'onException',
                'UNHANDLED_EXCEPTION',
            ));
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }
    }

    /**
     * Collects and transmits all captured data when PHP finishes processing.
     *
     * This method is registered as a shutdown handler using register_shutdown_function()
     * in TreblleFactory. It runs at the end of the request lifecycle and:
     * 1. Builds the complete payload from all data providers
     * 2. Encodes the payload as JSON
     * 3. Transmits the data to Treblle
     *
     * Transmission Strategy:
     * - If pcntl_fork is unavailable or disabled: Sends data in main process (blocking)
     * - If pcntl_fork is available and enabled:
     *   - Forks a child process to send data (non-blocking)
     *   - Parent process exits immediately
     *   - Child process sends data then terminates itself
     *
     * Error Handling:
     * - Payload building errors: Sends error message to Treblle instead
     * - Debug OFF: Falls back to error payload, doesn't throw
     * - Debug ON: Re-throws exceptions for troubleshooting
     *
     * Background Processing Flow:
     * 1. Attempt to fork process using pcntl_fork()
     * 2. If fork fails (returns -1): Fall back to blocking transmission
     * 3. If fork succeeds (returns 0 in child): Child sends data and kills itself
     * 4. Parent process (PID > 0): Continues/exits without waiting
     *
     * @return void
     * @throws Throwable Only in debug mode if an exception occurs during shutdown
     */
    public function onShutdown(): void
    {
        try {
            $payload = $this->buildPayload();
            $payload = json_encode($payload);
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }

            $payload = '{"error": "could not convert payload to valid json in sdk"}';
        }

        if (! function_exists('pcntl_fork') || false === $this->forkProcess) {
            $this->collectData($payload);

            return;
        }

        $pid = pcntl_fork();

        if ($this->isUnableToForkProcess($pid)) {
            $this->collectData($payload);

            return;
        }

        if ($this->isChildProcess($pid)) {
            $this->collectData($payload);
            $this->killProcessWithId((int) getmypid());
        }
    }

    /**
     * Gets the Treblle API endpoint URL for data transmission.
     *
     * Returns either the custom URL provided during construction, or randomly
     * selects one of three default Treblle endpoints for load balancing:
     * - https://rocknrolla.treblle.com
     * - https://punisher.treblle.com
     * - https://sicario.treblle.com
     *
     * Random selection distributes load across Treblle's infrastructure.
     *
     * @return string The base URL for Treblle API requests
     */
    public function getBaseUrl(): string
    {
        $urls = [
            'https://rocknrolla.treblle.com',
            'https://punisher.treblle.com',
            'https://sicario.treblle.com',
        ];

        return $this->url ?? $urls[array_rand($urls)];
    }

    /**
     * Sets the SDK version number sent to Treblle.
     *
     * This is typically used by framework-specific integrations (Laravel, Symfony, etc.)
     * to identify which integration is being used. The default is 5.0 for vanilla PHP.
     *
     * @param float $version The version number to set
     * @return self Fluent interface for method chaining
     */
    public function setVersion(float $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Sets the SDK name identifier sent to Treblle.
     *
     * This is typically used by framework-specific integrations to identify
     * the platform. For example:
     * - 'php' for vanilla PHP (default)
     * - 'laravel' for Laravel integration
     * - 'symfony' for Symfony integration
     *
     * @param string $name The SDK name to set
     * @return self Fluent interface for method chaining
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Builds the complete payload for transmission to Treblle.
     *
     * Collects data from all registered providers and assembles it into
     * a structured array containing:
     * - api_key: Project API key for authentication
     * - sdk_token: SDK token for authorization
     * - sdk: SDK name identifier (e.g., 'php', 'laravel')
     * - version: SDK version number
     * - data: Complete request/response/error data from all providers
     *
     * Data Collection:
     * - Server info (OS, protocol, timezone) from ServerDataProvider
     * - Language info (PHP version) from LanguageDataProvider
     * - Request data (headers, body, method) from RequestDataProvider
     * - Response data (status, body, headers) from ResponseDataProvider
     * - Error data (exceptions, warnings) from ErrorDataProvider
     *
     * All sensitive fields are masked before inclusion in the payload.
     *
     * Error Handling:
     * - Debug OFF: Returns empty array on failure
     * - Debug ON: Re-throws exceptions for troubleshooting
     *
     * @return array<string, mixed> The complete payload array, or empty array on failure
     * @throws Throwable Only in debug mode if an exception occurs during payload building
     */
    private function buildPayload(): array
    {
        try {
            return [
                'api_key' => $this->apiKey,
                'sdk_token' => $this->sdkToken,
                'sdk' => $this->name,
                'version' => $this->version,
                'data' => new Data(
                    $this->serverDataProvider->getServer(),
                    $this->languageDataProvider->getLanguage(),
                    $this->requestDataProvider->getRequest(),
                    $this->responseDataProvider->getResponse(),
                    $this->errorDataProvider->getErrors()
                ),
            ];
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }

        return [];
    }

    /**
     * Transmits the JSON payload to Treblle's API endpoints.
     *
     * Sends an HTTP POST request to Treblle with the JSON-encoded payload.
     * Uses Guzzle HTTP client with the following configuration:
     * - Method: POST
     * - Endpoint: Random Treblle URL from getBaseUrl()
     * - Connection timeout: 3 seconds
     * - Request timeout: 3 seconds
     * - SSL verification: Disabled
     * - HTTP errors: Suppressed (doesn't throw on 4xx/5xx)
     * - Headers:
     *   - Content-Type: application/json
     *   - x-api-key: SDK token for authentication
     *
     * Short timeouts ensure minimal impact on application performance.
     * Errors during transmission are silently ignored unless debug mode is enabled.
     *
     * Error Handling:
     * - Debug OFF: Silently catches and ignores transmission failures
     * - Debug ON: Re-throws exceptions for troubleshooting
     *
     * @param string $payload The JSON-encoded payload to transmit
     * @return void
     * @throws Throwable Only in debug mode if transmission fails
     * @throws GuzzleException Only in debug mode if HTTP request fails
     */
    private function collectData(string $payload): void
    {
        try {
            $this->client->request(
                'POST',
                $this->getBaseUrl(),
                [
                    'connect_timeout' => 3,
                    'timeout' => 3,
                    'verify' => false,
                    'http_errors' => false,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'x-api-key' => $this->sdkToken,
                    ],
                    'body' => $payload,
                ]
            );
        } catch (Throwable $throwable) {
            if ($this->debug) {
                throw $throwable;
            }
        }
    }

    /**
     * Checks if the current process is the child process after forking.
     *
     * After calling pcntl_fork():
     * - Returns 0 in the child process
     * - Returns positive PID in the parent process
     * - Returns -1 if fork failed
     *
     * @param int $pid The process ID returned by pcntl_fork()
     * @return bool True if this is the child process (PID === 0)
     */
    private function isChildProcess(int $pid): bool
    {
        return 0 === $pid;
    }

    /**
     * Checks if process forking failed.
     *
     * pcntl_fork() returns -1 when unable to fork a new process,
     * typically due to system resource limitations.
     *
     * @param int $pid The process ID returned by pcntl_fork()
     * @return bool True if fork failed (PID === -1)
     */
    private function isUnableToForkProcess(int $pid): bool
    {
        return -1 === $pid;
    }

    /**
     * Terminates a process by its process ID.
     *
     * Uses platform-specific commands to kill the process:
     * - Windows: taskkill /F /T /PID {pid}
     * - Unix/Linux/macOS: kill -9 {pid}
     *
     * This is used to terminate the child process after it finishes
     * transmitting data to Treblle in background mode.
     *
     * @param int $pid The process ID to terminate
     * @return void
     */
    private function killProcessWithId(int $pid): void
    {
        'WIN' === mb_strtoupper(mb_substr(PHP_OS, 0, 3)) ? exec("taskkill /F /T /PID {$pid}") : exec("kill -9 {$pid}");
    }
}
