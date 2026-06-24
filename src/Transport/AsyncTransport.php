<?php

declare(strict_types=1);

namespace Treblle\Php\Transport;

use Treblle\Php\Http\IngressClient;

class AsyncTransport
{
    public function __construct(
        private readonly IngressClient $client,
        private readonly bool $debug = false,
    ) {
    }

    public function send(string $gzippedPayload, string $endpoint, string $sdkToken): void
    {
        // Strategy 1: PHP-FPM fastcgi_finish_request
        // Flushes the response to the client, then continues running in the background.
        if (function_exists('fastcgi_finish_request')) {
            if ($this->debug) {
                error_log('[TREBLLE] Async: using fastcgi_finish_request');
            }

            if (ob_get_level() > 0) {
                ob_end_flush();
            }

            fastcgi_finish_request();

            $this->client->send($gzippedPayload, $endpoint, $sdkToken, $this->debug);

            return;
        }

        // Strategy 2: pcntl_fork (Unix/Linux only)
        // Fork a child process to send data; parent returns immediately.
        if (function_exists('pcntl_fork')) {
            if ($this->debug) {
                error_log('[TREBLLE] Async: using pcntl_fork');
            }

            if (ob_get_level() > 0) {
                ob_end_flush();
            }

            $pid = pcntl_fork();

            if ($pid === -1) {
                // Fork failed — fall through to sync
            } elseif ($pid === 0) {
                // Child process: send and exit
                $this->client->send($gzippedPayload, $endpoint, $sdkToken, $this->debug);
                exit(0);
            } else {
                // Parent: reap child to avoid zombies
                pcntl_waitpid($pid, $status, WNOHANG);

                return;
            }
        }

        // Strategy 3: Sync fallback
        if ($this->debug) {
            error_log('[TREBLLE] Async: using sync fallback');
        }

        if (ob_get_level() > 0) {
            ob_end_flush();
        }

        $this->client->send($gzippedPayload, $endpoint, $sdkToken, $this->debug);
    }
}
