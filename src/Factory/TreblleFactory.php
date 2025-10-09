<?php

declare(strict_types=1);

namespace Treblle\Php\Factory;

use GuzzleHttp\Client;
use Treblle\Php\Treblle;
use Treblle\Php\Helpers\SensitiveDataMasker;
use Treblle\Php\DataProviders\PhpLanguageDataProvider;
use Treblle\Php\DataProviders\InMemoryErrorDataProvider;
use Treblle\Php\DataProviders\SuperGlobalsServerDataProvider;
use Treblle\Php\DataProviders\SuperGlobalsRequestDataProvider;
use Treblle\Php\DataProviders\OutputBufferingResponseDataProvider;

/**
 * Factory for creating and configuring Treblle SDK instances.
 *
 * This factory provides a streamlined way to instantiate Treblle with sensible
 * defaults while allowing full customization through configuration options.
 * It automatically sets up data providers, error handlers, and field masking.
 *
 * Features:
 * - Default masked fields for common sensitive data (passwords, credit cards, etc.)
 * - Automatic error handler registration (can be disabled)
 * - Custom data provider support
 * - Header filtering with pattern matching
 * - Debug mode for development
 * - Optional background processing via pcntl_fork
 *
 * @package Treblle\Php\Factory
 */
final class TreblleFactory
{
    /**
     * Private constructor to prevent direct instantiation.
     *
     * Use TreblleFactory::create() instead.
     */
    private function __construct()
    {
    }

    /**
     * Creates and configures a new Treblle SDK instance.
     *
     * This is the main entry point for creating Treblle instances. It sets up
     * all necessary components including data providers, field masking, and
     * error handlers.
     *
     * Default masked fields (case-insensitive):
     * - password, pwd, secret, password_confirmation
     * - cc, card_number, ccv
     * - ssn, credit_score
     *
     * Configuration options:
     * - client: Custom GuzzleHttp\ClientInterface instance
     * - url: Custom Treblle endpoint URL
     * - fork_process: Enable background processing (requires pcntl extension)
     * - register_handlers: Auto-register error/exception handlers (default: true)
     * - server_provider: Custom ServerDataProvider implementation
     * - language_provider: Custom LanguageDataProvider implementation
     * - request_provider: Custom RequestDataProvider implementation
     * - response_provider: Custom ResponseDataProvider implementation
     * - error_provider: Custom ErrorDataProvider implementation
     *
     * @param string $apiKey Your Treblle project API key
     * @param string $sdkToken Your Treblle SDK token
     * @param bool $debug Enable debug mode (throws exceptions instead of silent failures)
     * @param list<string> $maskedFields Additional fields to mask beyond defaults
     * @param list<string> $excludedHeaders Header patterns to exclude (exact, wildcard, or regex)
     * @param array<string, mixed> $config Advanced configuration options
     * @return Treblle Configured Treblle instance ready to capture API data
     */
    public static function create(
        string $apiKey,
        string $sdkToken,
        bool $debug = false,
        array $maskedFields = [],
        array $excludedHeaders = [],
        array $config = []
    ): Treblle {
        $defaultMaskedFields = [
            'password',
            'pwd',
            'secret',
            'password_confirmation',
            'cc',
            'card_number',
            'ccv',
            'ssn',
            'credit_score',
        ];

        $maskedFields = array_unique(array_merge($defaultMaskedFields, $maskedFields));

        $masker = new SensitiveDataMasker($maskedFields);

        $errorDataProvider = new InMemoryErrorDataProvider();

        $treblle = new Treblle(
            apiKey: $apiKey,
            sdkToken: $sdkToken,
            client: $config['client'] ?? new Client(),
            serverDataProvider: $config['server_provider'] ?? new SuperGlobalsServerDataProvider(),
            languageDataProvider: $config['language_provider'] ?? new PhpLanguageDataProvider(),
            requestDataProvider: $config['request_provider'] ?? new SuperGlobalsRequestDataProvider($masker, $excludedHeaders),
            responseDataProvider: $config['response_provider'] ?? new OutputBufferingResponseDataProvider($masker, $errorDataProvider, $excludedHeaders),
            errorDataProvider: $config['error_provider'] ?? $errorDataProvider,
            debug: $debug,
            url: $config['url'] ?? null,
            forkProcess: $config['fork_process'] ?? false,
        );

        if ($config['register_handlers'] ?? true) {
            set_error_handler([$treblle, 'onError']);
            set_exception_handler([$treblle, 'onException']);
            register_shutdown_function([$treblle, 'onShutdown']);
        }

        return $treblle;
    }
}
