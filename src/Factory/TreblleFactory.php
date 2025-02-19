<?php

declare(strict_types=1);

namespace Treblle\Php\Factory;

use GuzzleHttp\Client;
use Treblle\Php\Treblle;
use Treblle\Php\FieldMasker;
use Treblle\Php\PhpLanguageDataProvider;
use Treblle\Php\InMemoryErrorDataProvider;
use Treblle\Php\SuperGlobalsServerDataProvider;
use Treblle\Php\SuperGlobalsRequestDataProvider;
use Treblle\Php\OutputBufferingResponseDataProvider;

final class TreblleFactory
{
    /**
     * @param list<string> $maskedFields
     * @param array<string, mixed> $config
     */
    public static function create(
        string $apiKey,
        string $projectId,
        bool $debug = false,
        array $maskedFields = [],
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

        $masker = new FieldMasker($maskedFields);

        $errorDataProvider = new InMemoryErrorDataProvider();

        $treblle = new Treblle(
            apiKey: $apiKey,
            projectId: $projectId,
            client: $config['client'] ?? new Client(),
            serverDataProvider: $config['server_provider'] ?? new SuperGlobalsServerDataProvider(),
            languageDataProvider: $config['language_provider'] ?? new PhpLanguageDataProvider(),
            requestDataProvider: $config['request_provider'] ?? new SuperGlobalsRequestDataProvider($masker),
            responseDataProvider: $config['response_provider'] ?? new OutputBufferingResponseDataProvider($masker, $errorDataProvider),
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
