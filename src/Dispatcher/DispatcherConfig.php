<?php

declare(strict_types=1);

namespace Origamy\Dispatcher;

use Origamy\LoggerInterface;

class DispatcherConfig
{
    /** @var string */
    public $endpoint;
    /** @var string */
    public $writeKey;
    /** @var string */
    public $version;
    /** @var bool */
    public $verbose;
    /** @var LoggerInterface|null */
    public $logger;

    public function __construct(
        string $endpoint = '',
        string $writeKey = '',
        string $version  = '3.0.0',
        bool   $verbose  = false,
        ?LoggerInterface $logger = null
    ) {
        $this->endpoint = $endpoint;
        $this->writeKey = $writeKey;
        $this->version  = $version;
        $this->verbose  = $verbose;
        $this->logger   = $logger;
    }
}
