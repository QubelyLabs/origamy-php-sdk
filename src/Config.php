<?php

declare(strict_types=1);

namespace Origamy;

use Origamy\Dispatcher\DispatcherInterface;
use Origamy\Exceptions\ConfigException;
use Origamy\Queue\QueueInterface;

class Config
{
    public const VERSION          = '3.0.0';
    public const DEFAULT_ENDPOINT = 'https://api.origamy.com';
    public const DEFAULT_INTERVAL_MS = 5_000;
    public const DEFAULT_BATCH_SIZE  = 250;

    public string $endpoint;
    public int $intervalMs;
    public ?DispatcherInterface $dispatcher;
    public ?QueueInterface $queue;
    public int $queueCapacity;
    public LoggerInterface $logger;
    public ?CallbackInterface $callback;
    public int $batchSize;
    public bool $verbose;
    public ?Context $defaultContext;

    /** @var callable(int): int  Returns milliseconds to wait before retry attempt $n */
    public $retryAfter;

    /** @internal used for testing only */
    public ?\Closure $uid;

    /** @internal used for testing only */
    public ?\Closure $now;

    /** @internal used for testing only */
    public int $maxConcurrentRequests;

    public function __construct(
        string $endpoint = '',
        int $intervalMs = 0,
        ?DispatcherInterface $dispatcher = null,
        ?QueueInterface $queue = null,
        int $queueCapacity = 0,
        ?LoggerInterface $logger = null,
        ?CallbackInterface $callback = null,
        int $batchSize = 0,
        bool $verbose = false,
        ?Context $defaultContext = null,
        ?callable $retryAfter = null,
        ?\Closure $uid = null,
        ?\Closure $now = null,
        int $maxConcurrentRequests = 0,
    ) {
        $this->endpoint             = $endpoint;
        $this->intervalMs           = $intervalMs;
        $this->dispatcher           = $dispatcher;
        $this->queue                = $queue;
        $this->queueCapacity        = $queueCapacity;
        $this->logger               = $logger ?? new StdLogger();
        $this->callback             = $callback;
        $this->batchSize            = $batchSize;
        $this->verbose              = $verbose;
        $this->defaultContext       = $defaultContext;
        $this->retryAfter           = $retryAfter ?? [self::class, 'defaultRetryAfter'];
        $this->uid                  = $uid;
        $this->now                  = $now;
        $this->maxConcurrentRequests = $maxConcurrentRequests;
    }

    /**
     * Validate configuration values.
     *
     * @throws ConfigException
     */
    public function validate(): void
    {
        if ($this->intervalMs < 0) {
            throw new ConfigException(
                'negative time intervals are not supported',
                'Interval',
                $this->intervalMs,
            );
        }
        if ($this->batchSize < 0) {
            throw new ConfigException(
                'negative batch sizes are not supported',
                'BatchSize',
                $this->batchSize,
            );
        }
    }

    /** Fill in zero-value fields with their defaults, return resolved config. */
    public function resolve(): self
    {
        $c = clone $this;

        if ($c->endpoint === '') {
            $c->endpoint = self::DEFAULT_ENDPOINT;
        }
        if ($c->intervalMs === 0) {
            $c->intervalMs = self::DEFAULT_INTERVAL_MS;
        }
        if ($c->batchSize === 0) {
            $c->batchSize = self::DEFAULT_BATCH_SIZE;
        }
        if ($c->queueCapacity <= 0) {
            $c->queueCapacity = 100;
        }
        if ($c->maxConcurrentRequests <= 0) {
            $c->maxConcurrentRequests = 1000;
        }
        if ($c->defaultContext === null) {
            $c->defaultContext = new Context();
        }
        if ($c->uid === null) {
            $c->uid = static fn () => \Ramsey\Uuid\Uuid::uuid4()->toString();
        }
        if ($c->now === null) {
            $c->now = static fn (): \DateTimeImmutable => new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        }

        // Always overwrite library info so it is accurate.
        $c->defaultContext->library = new LibraryInfo(
            name: 'origamy-php',
            version: self::VERSION,
        );

        return $c;
    }

    /** Exponential backoff matching backo-go DefaultBacko: base=100ms, factor=2, cap=10s. */
    public static function defaultRetryAfter(int $attempt): int
    {
        return (int) min(100 * (2 ** $attempt), 10_000);
    }
}
