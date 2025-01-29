<?php

declare(strict_types=1);

namespace Treblle;

use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;
use Treblle\Contract\RequestDataProvider;
use Treblle\Model\Request;

class SuperGlobalsRequestDataProvider implements RequestDataProvider
{
    public function __construct(private FieldMasker $masker) {}

    public function getRequest(): Request
    {
        return new Request(
            \Safe\gmdate('Y-m-d H:i:s'),
            $this->getClientIpAddress(),
            $this->getEndpointUrl(),
            $this->getUserAgent(),
            $_SERVER['REQUEST_METHOD'] ?? null,
            \Safe\getallheaders(),
            $this->masker->mask($_REQUEST),
            $this->getRawPayload(),
        );
    }

    /**
     * Get the IP address of the requester.
     *
     * @todo add option for trusted proxies.
     */
    protected function getClientIpAddress(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        return $ip_address;
    }

    /**
     * Get the current request endpoint url.
     */
    protected function getEndpointUrl(): string
    {
        $protocol = $_SERVER['HTTPS'] ?? null !== 'off' ? 'https://' : 'http://';

        return $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }

    /**
     * Get the user agent.
     */
    protected function getUserAgent(): string
    {
        $user_agent = '';

        if (isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT'])) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
        }

        return $user_agent;
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function getRawPayload(): array
    {
        try {
            $rawBody = \Safe\json_decode(\Safe\file_get_contents('php://input'), true);

            return $this->masker->mask($rawBody);
        } catch (FilesystemException|JsonException $exception) {
            return [];
        }
    }
}
