<?php

declare(strict_types=1);

namespace Treblle\Factory;

use Treblle\Core\DataProviders\ErrorProvider;
use Treblle\Core\DataProviders\GlobalRequestProvider;
use Treblle\Core\DataProviders\GlobalServerProvider;
use Treblle\Core\DataProviders\LanguageProvider;
use Treblle\Core\DataProviders\OutputBufferResponseProvider;
use Treblle\Core\Masking\FieldMasker;
use Treblle\Core\Support\PHP;
use Treblle\Treblle;

final class TreblleFactory
{
    public static function create(
        string $apiKey,
        string $projectId,
        array $maskedFields = [],
        bool $debug = false,
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

        $masker = new FieldMasker(
            fields: array_merge(
                $defaultMaskedFields,
                $maskedFields,
            ),
        );

        $error = new ErrorProvider();


        $treblle = new Treblle(
            apiKey: $apiKey,
            projectId: $projectId,
            server: new GlobalServerProvider(),
            language: new LanguageProvider(
                php: new PHP(),
            ),
            request: new GlobalRequestProvider(
                masker: $masker,
            ),
            response: new OutputBufferResponseProvider(
                error: $error,
                masker: $masker,
            ),
            error: $error,
            masker: $masker,
            debug: $debug,
        );

        set_error_handler([$treblle, 'onError']);
        set_exception_handler([$treblle, 'onException']);
        register_shutdown_function([$treblle, 'onShutdown']);

        return $treblle;
    }
}
