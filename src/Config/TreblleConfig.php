<?php

declare(strict_types=1);

namespace Treblle\Php\Config;

readonly class TreblleConfig
{
    public const INGRESS_URL = 'https://ingress.treblle.com';
    public const SDK_NAME = 'php';
    public const SDK_VERSION = 60;

    /** @var string[] */
    public const DEFAULT_MASKED_KEYWORDS = [
        'password',
        'pwd',
        'secret',
        'password_confirmation',
        'passwordConfirmation',
        'cc',
        'card_number',
        'cardNumber',
        'ccv',
        'ssn',
        'credit_score',
        'creditScore',
        'authorization',
        'x-api-key',
        'cookie',
        'set-cookie',
    ];

    /**
     * @param string[] $maskedKeywords
     * @param string[] $excludedPaths
     */
    public function __construct(
        public string $sdkToken,
        public string $apiKey,
        public bool $debug = false,
        public array $maskedKeywords = self::DEFAULT_MASKED_KEYWORDS,
        public array $excludedPaths = [],
        public ?string $customIngress = null,
        public bool $enabled = true,
    ) {
        if ($this->debug) {
            if ($this->sdkToken === '') {
                error_log('[TREBLLE] SDK token is missing');
            }
            if ($this->apiKey === '') {
                error_log('[TREBLLE] API key is missing');
            }
        }
    }

    public function getIngressUrl(): string
    {
        return $this->customIngress ?? self::INGRESS_URL;
    }
}
