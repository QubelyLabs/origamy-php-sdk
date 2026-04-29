<?php

declare(strict_types=1);

namespace Origamy\Queue;

class ErrQueueFull extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('queue is full');
    }
}
