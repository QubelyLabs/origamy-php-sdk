<?php

declare(strict_types=1);

namespace Origamy\Dispatcher;

use Origamy\LoggerInterface;

class DispatcherConfig
{
    public function __construct(
        public string          $endpoint = '',
        public string          $writeKey = '',
        public string          $version  = '3.0.0',
        public bool            $verbose  = false,
        public ?LoggerInterface $logger  = null,
    ) {}
}
