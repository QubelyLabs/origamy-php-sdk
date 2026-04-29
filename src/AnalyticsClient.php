<?php

declare(strict_types=1);

namespace Origamy;

use Origamy\Dispatcher\DispatcherConfig;
use Origamy\Dispatcher\DispatcherInterface;
use Origamy\Dispatcher\HttpDispatcher;
use Origamy\Exceptions\ConfigException;
use Origamy\Exceptions\FieldException;
use Origamy\Messages\Alias;
use Origamy\Messages\Group;
use Origamy\Messages\Identify;
use Origamy\Messages\Page;
use Origamy\Messages\Screen;
use Origamy\Messages\Track;
use Origamy\Queue\InMemoryQueue;
use Origamy\Queue\QueueInterface;

/**
 * PHP analytics client.
 *
 * Since PHP is single-threaded (no goroutines), messages are flushed:
 *  - synchronously when the batch size is reached,
 *  - on close(), and
 *  - automatically via a registered shutdown function.
 *
 * This preserves the external contract of the Go SDK while adapting to PHP's
 * execution model.
 */
class AnalyticsClient implements ClientInterface
{
    private const MAX_BATCH_BYTES   = 500_000;
    private const MAX_MESSAGE_BYTES = 32_000;

    private Config $config;
    private string $writeKey;
    private QueueInterface $queue;
    private DispatcherInterface $dispatcher;
    private bool $closed = false;

    /** Pending serialised messages for the current batch. */
    private array $pending = [];
    private int   $pendingBytes = 0;

    /** Factory: create and validate a client. Returns [$client, null] or [null, ConfigException]. */
    public static function newWithConfig(string $writeKey, Config $config): array
    {
        try {
            $config->validate();
        } catch (ConfigException $e) {
            return [null, $e];
        }

        $resolved = $config->resolve();
        return [new self($writeKey, $resolved), null];
    }

    public function __construct(string $writeKey, Config $config)
    {
        $this->writeKey   = $writeKey;
        $this->config     = $config;
        $this->queue      = $config->queue ?? new InMemoryQueue($config->queueCapacity);
        $this->dispatcher = $config->dispatcher ?? $this->makeDefaultDispatcher();

        register_shutdown_function(function (): void {
            if (!$this->closed) {
                try { $this->close(); } catch (\Throwable) {}
            }
        });
    }

    public function enqueue(MessageInterface $message): void
    {
        if ($this->closed) {
            throw new \RuntimeException('the client was already closed');
        }

        $message->validate();

        $uid = ($this->config->uid)();
        $now = ($this->config->now)();

        $enriched = $this->enrichMessage($message, $uid, $now);

        $this->push($enriched);
    }

    public function close(): void
    {
        if ($this->closed) {
            throw new \RuntimeException('the client was already closed');
        }
        $this->closed = true;
        $this->queue->close();

        // Drain any messages still in the queue (from enqueue → queue path)
        foreach ($this->queue->drain() as $msg) {
            $this->pushToPending($msg);
        }

        $this->flush();
        $this->dispatcher->close();
    }

    // -----------------------------------------------------------------------
    // Internal helpers
    // -----------------------------------------------------------------------

    private function enrichMessage(MessageInterface $message, string $uid, \DateTimeImmutable $now): MessageInterface
    {
        $defaultCtx = $this->config->defaultContext;

        if ($message instanceof Alias) {
            $m = clone $message;
            $m->type      = 'alias';
            $m->messageId = $this->makeMessageId($m->messageId, $uid);
            $m->timestamp = $this->makeTimestamp($m->timestamp, $now);
            if ($m->context === null) $m->context = $defaultCtx;
            return $m;
        }
        if ($message instanceof Group) {
            $m = clone $message;
            $m->type      = 'group';
            $m->messageId = $this->makeMessageId($m->messageId, $uid);
            $m->timestamp = $this->makeTimestamp($m->timestamp, $now);
            if ($m->context === null) $m->context = $defaultCtx;
            return $m;
        }
        if ($message instanceof Identify) {
            $m = clone $message;
            $m->type      = 'identify';
            $m->messageId = $this->makeMessageId($m->messageId, $uid);
            $m->timestamp = $this->makeTimestamp($m->timestamp, $now);
            if ($m->context === null) $m->context = $defaultCtx;
            return $m;
        }
        if ($message instanceof Page) {
            $m = clone $message;
            $m->type      = 'page';
            $m->messageId = $this->makeMessageId($m->messageId, $uid);
            $m->timestamp = $this->makeTimestamp($m->timestamp, $now);
            if ($m->context === null) $m->context = $defaultCtx;
            return $m;
        }
        if ($message instanceof Screen) {
            $m = clone $message;
            $m->type      = 'screen';
            $m->messageId = $this->makeMessageId($m->messageId, $uid);
            $m->timestamp = $this->makeTimestamp($m->timestamp, $now);
            if ($m->context === null) $m->context = $defaultCtx;
            return $m;
        }
        if ($message instanceof Track) {
            $m = clone $message;
            $m->type      = 'track';
            $m->messageId = $this->makeMessageId($m->messageId, $uid);
            $m->timestamp = $this->makeTimestamp($m->timestamp, $now);
            if ($m->context === null) $m->context = $defaultCtx;
            return $m;
        }

        throw new \InvalidArgumentException(
            sprintf('messages with custom types cannot be enqueued: %s', get_class($message))
        );
    }

    private function makeMessageId(string $id, string $def): string
    {
        return $id !== '' ? $id : $def;
    }

    private function makeTimestamp(?\DateTimeImmutable $t, \DateTimeImmutable $def): \DateTimeImmutable
    {
        return $t ?? $def;
    }

    private function push(MessageInterface $message): void
    {
        $json = json_encode($message->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $err = new \RuntimeException('failed to serialize message: ' . json_last_error_msg());
            $this->config->logger->errorf('%s - %s', $err->getMessage(), get_class($message));
            $this->notifyFailure([$message], $err);
            return;
        }

        $byteLen = strlen($json);
        if ($byteLen > self::MAX_MESSAGE_BYTES) {
            $err = new \RuntimeException('the message exceeds the maximum allowed size');
            $this->config->logger->errorf('%s - %s', $err->getMessage(), get_class($message));
            $this->notifyFailure([$message], $err);
            return;
        }

        $this->debugf('buffer (%d/%d) %s', count($this->pending), $this->config->batchSize, get_class($message));

        // If adding this message would overflow the byte budget, flush first.
        if ($this->pendingBytes + $byteLen + 1 > $this->maxBatchBytes()) {
            $this->flush();
        }

        $this->pending[]      = ['msg' => $message, 'json' => $json];
        $this->pendingBytes  += $byteLen;

        if (count($this->pending) >= $this->config->batchSize) {
            $this->debugf(
                'exceeded messages batch limit with batch of %d messages – flushing',
                count($this->pending)
            );
            $this->flush();
        }
    }

    private function pushToPending(MessageInterface $message): void
    {
        $this->push($message);
    }

    private function flush(): void
    {
        if (count($this->pending) === 0) {
            return;
        }

        $msgs          = $this->pending;
        $this->pending = [];
        $this->pendingBytes = 0;

        $this->debugf('flushing %d messages', count($msgs));
        $this->sendBatch($msgs);
    }

    private function sendBatch(array $msgs): void
    {
        $now    = ($this->config->now)();
        $sentAt = $now->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.') .
                  sprintf('%03d', (int) $now->format('v')) . 'Z';

        $jsonItems = array_map(fn ($m) => $m['json'], $msgs);
        $payload   = '{"sentAt":' . json_encode($sentAt) . ',"batch":[' . implode(',', $jsonItems) . ']}';

        $messages   = array_map(fn ($m) => $m['msg'], $msgs);
        $attempts   = 10;
        $lastErr    = null;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $this->dispatcher->send($payload);
                $this->notifySuccess($messages);
                return;
            } catch (\Throwable $e) {
                $lastErr = $e;

                if ($i < $attempts - 1) {
                    $waitMs = ($this->config->retryAfter)($i);
                    if ($this->closed) {
                        break;
                    }
                    usleep($waitMs * 1000);
                }
            }
        }

        if ($this->closed) {
            $this->config->logger->errorf(
                '%d messages dropped because they failed to be sent and the client was closed',
                count($msgs)
            );
        } else {
            $this->config->logger->errorf(
                '%d messages dropped because they failed to be sent after %d attempts',
                count($msgs),
                $attempts
            );
        }
        $this->notifyFailure($messages, $lastErr ?? new \RuntimeException('send failed'));
    }

    private function notifySuccess(array $messages): void
    {
        if ($this->config->callback === null) return;
        foreach ($messages as $msg) {
            $this->config->callback->success($msg);
        }
    }

    private function notifyFailure(array $messages, \Throwable $err): void
    {
        if ($this->config->callback === null) return;
        foreach ($messages as $msg) {
            $this->config->callback->failure($msg, $err);
        }
    }

    private function maxBatchBytes(): int
    {
        $now    = ($this->config->now)();
        $sentAt = $now->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.') .
                  sprintf('%03d', (int) $now->format('v')) . 'Z';
        $empty  = '{"sentAt":' . json_encode($sentAt) . ',"batch":[]}';
        return self::MAX_BATCH_BYTES - strlen($empty);
    }

    private function makeDefaultDispatcher(): DispatcherInterface
    {
        return new HttpDispatcher(new DispatcherConfig(
            endpoint: $this->config->endpoint,
            writeKey: $this->writeKey,
            version:  Config::VERSION,
            verbose:  $this->config->verbose,
            logger:   $this->config->logger,
        ));
    }

    private function debugf(string $format, mixed ...$args): void
    {
        if ($this->config->verbose) {
            $this->config->logger->logf($format, ...$args);
        }
    }
}
