<?php

declare(strict_types=1);

namespace Treblle\Php;

use Treblle\Php\DataTransferObject\Request;
use Treblle\Php\Contract\RequestDataProvider;

final class SuperGlobalsRequestDataProvider implements RequestDataProvider
{
    public function __construct(private FieldMasker $masker)
    {
    }

    public function getRequest(): Request
    {
        return new Request(
            timestamp: gmdate('Y-m-d H:i:s'),
            url: $this->getEndpointUrl(),
            ip: $this->getClientIpAddress(),
            user_agent: $this->getUserAgent(),
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            headers: getallheaders(),
            body: $this->masker->mask($_REQUEST),
        );
    }

    /**
     * Get the IP address of the requester if you cannot get it return bogon.
     */
    private function getClientIpAddress(): string
    {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'bogon';

        if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $ipAddress;
    }

    /**
     * Get the current request endpoint url.
     */
    private function getEndpointUrl(): string
    {
        $protocol = $_SERVER['HTTPS'] ?? null !== 'off' ? 'https://' : 'http://';

        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Get the user agent.
     */
    private function getUserAgent(): string
    {
        $userAgent = '';

        if (! empty($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
        }

        return $userAgent;
    }
}
