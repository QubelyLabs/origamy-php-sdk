<?php

declare(strict_types=1);

namespace Origamy\Tests;

use Origamy\Queue\ErrQueueClosed;
use Origamy\Queue\ErrQueueFull;
use Origamy\Queue\InMemoryQueue;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    public function testEnqueueDequeue(): void
    {
        $q = new InMemoryQueue(10);
        $q->enqueue('msg1');

        $msg = $q->dequeue();
        $this->assertSame('msg1', $msg);
    }

    public function testDequeueFromEmpty(): void
    {
        $q = new InMemoryQueue(10);
        $this->assertNull($q->dequeue());
    }

    public function testLen(): void
    {
        $q = new InMemoryQueue(10);
        $this->assertSame(0, $q->len());

        $q->enqueue('a');
        $q->enqueue('b');
        $this->assertSame(2, $q->len());
    }

    public function testCapacityFull(): void
    {
        $q = new InMemoryQueue(1);
        $q->enqueue('msg1');

        $this->expectException(ErrQueueFull::class);
        $q->enqueue('msg2');
    }

    public function testClose(): void
    {
        $q = new InMemoryQueue(10);
        $this->assertFalse($q->isClosed());

        $q->close();
        $this->assertTrue($q->isClosed());
    }

    public function testEnqueueAfterClose(): void
    {
        $q = new InMemoryQueue(10);
        $q->close();

        $this->expectException(ErrQueueClosed::class);
        $q->enqueue('msg');
    }

    public function testDrain(): void
    {
        $q = new InMemoryQueue(10);
        $q->enqueue('msg1');
        $q->enqueue('msg2');
        $q->enqueue('msg3');

        $q->close();
        $drained = $q->drain();

        $this->assertCount(3, $drained);
        $this->assertSame('msg1', $drained[0]);
        $this->assertSame('msg2', $drained[1]);
        $this->assertSame('msg3', $drained[2]);
    }

    public function testDrainEmptyQueue(): void
    {
        $q = new InMemoryQueue(10);
        $q->close();
        $this->assertSame([], $q->drain());
    }

    public function testFifoOrdering(): void
    {
        $q = new InMemoryQueue(10);
        $q->enqueue('first');
        $q->enqueue('second');
        $q->enqueue('third');

        $this->assertSame('first',  $q->dequeue());
        $this->assertSame('second', $q->dequeue());
        $this->assertSame('third',  $q->dequeue());
    }
}
