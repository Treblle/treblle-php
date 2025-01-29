<?php

declare(strict_types=1);

namespace Treblle\Factory;

use GuzzleHttp\Client;
use Treblle\FieldMasker;
use Treblle\InMemoryErrorDataProvider;
use Treblle\OutputBufferingResponseDataProvider;
use Treblle\PhpHelper;
use Treblle\PhpLanguageDataProvider;
use Treblle\SuperGlobalsRequestDataProvider;
use Treblle\SuperGlobalsServerDataProvider;
use Treblle\Treblle;

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
        $phpHelper = new PhpHelper();
        $errorDataProvider = new InMemoryErrorDataProvider();
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

        $treblle = new Treblle(
            apiKey: $apiKey,
            projectId: $projectId,
            client: $config['client'] ?? new Client(),
            serverDataProvider: new SuperGlobalsServerDataProvider(),
            languageDataProvider: new PhpLanguageDataProvider($phpHelper),
            requestDataProvider: $config['request_provider'] ?? new SuperGlobalsRequestDataProvider($masker),
            responseDataProvider: $config['response_provider'] ?? new OutputBufferingResponseDataProvider($masker, $errorDataProvider),
            errorDataProvider: $errorDataProvider,
            debug: $debug,
            url: $config['url'] ?? null,
            forkProcess: $config['fork_process'] ?? false,
        );

        set_error_handler([$treblle, 'onError']);
        set_exception_handler([$treblle, 'onException']);
        register_shutdown_function([$treblle, 'onShutdown']);

        return $treblle;
    }
}
