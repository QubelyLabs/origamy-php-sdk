<?php

declare(strict_types=1);

namespace Origamy\Queue;

class InMemoryQueue implements QueueInterface
{
    /** @var \SplQueue */
    private $queue;
    private bool $closed = false;
    private int $capacity;

    public function __construct(int $capacity = 100)
    {
        $this->capacity = $capacity > 0 ? $capacity : 100;
        $this->queue    = new \SplQueue();
    }

    public function enqueue($msg): void
    {
        if ($this->closed) {
            throw new ErrQueueClosed();
        }
        if ($this->queue->count() >= $this->capacity) {
            throw new ErrQueueFull();
        }
        $this->queue->enqueue($msg);
    }

    public function dequeue()
    {
        if ($this->queue->isEmpty()) {
            return null;
        }
        return $this->queue->dequeue();
    }

    public function drain(): array
    {
        $items = [];
        while (!$this->queue->isEmpty()) {
            $items[] = $this->queue->dequeue();
        }
        return $items;
    }

    public function len(): int
    {
        return $this->queue->count();
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function close(): void
    {
        $this->closed = true;
    }
}
