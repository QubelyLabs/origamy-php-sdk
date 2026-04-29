<?php

declare(strict_types=1);

namespace Origamy\Queue;

interface QueueInterface
{
    /** Add a message to the queue. */
    public function enqueue($msg): void;

    /** Remove and return the next message, or null if empty. */
    public function dequeue();

    /** Return all remaining messages and clear the queue. */
    public function drain(): array;

    /** Return the number of messages currently queued. */
    public function len(): int;

    /** Return true if the queue has been closed. */
    public function isClosed(): bool;

    /** Close the queue; further enqueue calls will throw. */
    public function close(): void;
}
