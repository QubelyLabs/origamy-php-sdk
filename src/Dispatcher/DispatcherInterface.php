<?php

declare(strict_types=1);

namespace Origamy\Dispatcher;

interface DispatcherInterface
{
    /**
     * Send a serialized batch payload to the analytics backend.
     *
     * @param string $payload JSON-encoded batch
     * @throws \RuntimeException on transport failure or non-2xx response
     */
    public function send(string $payload): void;

    /** Release any resources held by the dispatcher. */
    public function close(): void;
}
