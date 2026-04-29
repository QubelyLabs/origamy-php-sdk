<?php

declare(strict_types=1);

namespace Origamy\Tests\Helpers;

use Origamy\Dispatcher\DispatcherInterface;

class MockDispatcher implements DispatcherInterface
{
    public array $payloads = [];
    public int $sendCount  = 0;

    /** If set, every send() throws this error. */
    public ?\Throwable $error = null;

    public function send(string $payload): void
    {
        $this->sendCount++;
        if ($this->error !== null) {
            throw $this->error;
        }
        $this->payloads[] = $payload;
    }

    public function close(): void {}

    /** Decode and return the Nth sent payload as a PHP array. */
    public function getDecoded(int $index = 0): array
    {
        return json_decode($this->payloads[$index] ?? '{}', true) ?? [];
    }
}
