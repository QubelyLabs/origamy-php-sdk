<?php

declare(strict_types=1);

namespace Origamy;

interface ClientInterface
{
    /**
     * Queue a message to be sent.
     * Flushes the batch automatically when the batch size is reached.
     *
     * @throws \Origamy\Exceptions\FieldException  if the message is invalid
     * @throws \Origamy\Exceptions\ConfigException if the client is closed
     */
    public function enqueue(MessageInterface $message): void;

    /**
     * Flush remaining messages and release resources.
     * Calling close() more than once throws ErrClosed.
     *
     * @throws \RuntimeException if already closed
     */
    public function close(): void;
}
