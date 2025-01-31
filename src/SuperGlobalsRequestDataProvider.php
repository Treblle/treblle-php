<?php

declare(strict_types=1);

namespace Treblle;

use Exception;
use Treblle\DataTransferObject\Request;
use Treblle\Contract\RequestDataProvider;

final class SuperGlobalsRequestDataProvider implements RequestDataProvider
{
    public function __construct(private FieldMasker $masker)
    {
    }

    public function getRequest(): Request
    {
        return new Request(
            timestamp: gmdate('Y-m-d H:i:s'),
            ip: $this->getClientIpAddress(),
            url: $this->getEndpointUrl(),
            user_agent: $this->getUserAgent(),
            method: $_SERVER['REQUEST_METHOD'] ?? null,
            headers: getallheaders(),
            body: $this->masker->mask($_REQUEST),
            raw: $this->getRawPayload(),
        );
    }

    /**
     * Get the IP address of the requester if cannot get it return bogon.
     *
     * @todo add option for trusted proxies.
     */
    private function getClientIpAddress(): string
    {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'bogon';

        if (! empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $ip_address;
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
        $user_agent = '';

        if (isset($_SERVER['HTTP_USER_AGENT']) && ! empty($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
        }

        return $user_agent;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getRawPayload(): array
    {
        try {
            $rawBody = json_decode(file_get_contents('php://input'), true);

            return $this->masker->mask($rawBody ?? []);
        } catch (Exception) {
            return [];
        }
    }
}
