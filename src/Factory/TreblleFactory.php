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

final class TreblleFactory
{
    private function __construct()
    {
    }

    /**
     * @param list<string> $maskedFields
     * @param list<string> $excludedHeaders
     * @param array<string, mixed> $config
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
