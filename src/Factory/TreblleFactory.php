<?php

declare(strict_types=1);

namespace Treblle\Factory;

use GuzzleHttp\Client;
use Treblle\InMemoryErrorDataProvider;
use Treblle\OutputBufferingResponseDataProvider;
use Treblle\PayloadAnonymizer;
use Treblle\PhpHelper;
use Treblle\PhpLanguageDataProvider;
use Treblle\SuperglobalsRequestDataProvider;
use Treblle\SuperglobalsServerDataProvider;
use Treblle\Treblle;

final class TreblleFactory
{
    private const DEFAULT_ENDPOINT_URL = 'https://rocknrolla.treblle.com';

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
        $anonymizer = new PayloadAnonymizer($maskedFields);

        $treblle = new Treblle(
            $config['url'] ?? self::DEFAULT_ENDPOINT_URL,
            $apiKey,
            $projectId,
            $config['client'] ?? new Client(),
            new SuperglobalsServerDataProvider(),
            new PhpLanguageDataProvider($phpHelper),
            new SuperglobalsRequestDataProvider($anonymizer),
            new OutputBufferingResponseDataProvider($anonymizer, $errorDataProvider),
            $errorDataProvider,
            $debug
        );

        set_error_handler([$treblle, 'onError']);
        set_exception_handler([$treblle, 'onException']);
        register_shutdown_function([$treblle, 'onShutdown']);

        return $treblle;
    }
}
