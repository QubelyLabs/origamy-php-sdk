<?php

declare(strict_types=1);

namespace Origamy\Dispatcher;

class HttpDispatcher implements DispatcherInterface
{
    private int $timeoutSeconds;

    public function __construct(
        private DispatcherConfig $config,
        int $timeoutSeconds = 10,
    ) {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function send(string $payload): void
    {
        $endpoint = rtrim($this->config->endpoint ?: 'https://events.origamy.io', '/');
        $url      = $endpoint . '/v1/batch';
        $version  = $this->config->version ?: '3.0.0';

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl_init failed');
        }

        $auth = base64_encode($this->config->writeKey . ':');

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
                'Authorization: Basic ' . $auth,
                'User-Agent: origamy-php (version: ' . $version . ')',
            ],
        ]);

        $body       = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            $this->errorf('sending request - %s', $curlError);
            throw new \RuntimeException('curl error: ' . $curlError);
        }

        if ($statusCode >= 300) {
            $this->logf('response %d – %s', $statusCode, (string) $body);
            throw new \RuntimeException(sprintf('%d response from API', $statusCode));
        }

        $this->debugf('response %d', $statusCode);
    }

    public function close(): void {}

    private function debugf(string $format, mixed ...$args): void
    {
        if ($this->config->verbose && $this->config->logger !== null) {
            $this->config->logger->logf($format, ...$args);
        }
    }

    private function logf(string $format, mixed ...$args): void
    {
        $this->config->logger?->logf($format, ...$args);
    }

    private function errorf(string $format, mixed ...$args): void
    {
        $this->config->logger?->errorf($format, ...$args);
    }
}
