<?php

declare(strict_types=1);

namespace Origamy\Dispatcher;

/**
 * NoopDispatcher logs the batch payload to stdout instead of sending it.
 * Useful for development and debugging.
 */
class NoopDispatcher implements DispatcherInterface
{
    /** @var DispatcherConfig */
    private $config;
    /** @var resource */
    private $output;
    /** @var bool */
    private $colored;

    public function __construct(
        DispatcherConfig $config,
        $output = null,
        bool $colored = true
    ) {
        $this->config  = $config;
        $this->output  = $output ?? STDOUT;
        $this->colored = $colored;
    }

    public function send(string $payload): void
    {
        $batch = json_decode($payload, true);
        if ($batch === null) {
            $this->errorf('Failed to parse payload: %s', json_last_error_msg());
            return;
        }

        $messages = $batch['batch'] ?? [];
        $sentAt   = $batch['sentAt'] ?? '';
        $count    = count($messages);

        $this->printHeader($sentAt, $count);
        foreach ($messages as $i => $msg) {
            $this->printMessage($i + 1, $msg);
        }
        $this->printFooter();
    }

    public function close(): void {}

    private function printHeader(string $sentAt, int $count): void
    {
        $this->println('');
        $this->printLine('═', 60);
        $this->printf("  ORIGAMY SDK - NOOP DISPATCHER\n");
        $this->printLine('─', 60);
        $this->printf("  Sent At:   %s\n", $this->highlight($sentAt));
        $this->printf("  Messages:  %s\n", $this->highlight((string) $count));
        $this->printLine('═', 60);
    }

    private function printMessage(int $index, array $msg): void
    {
        $msgType     = $msg['type']        ?? '';
        $msgId       = $msg['messageId']   ?? '';
        $userId      = $msg['userId']      ?? '';
        $anonymousId = $msg['anonymousId'] ?? '';

        $this->println('');
        $this->printf("  Message #%d: %s\n", $index, $this->colorType(strtoupper($msgType)));
        $this->printLine('─', 50);

        $this->printf("    Message ID:   %s\n", $this->dim($msgId));
        if ($userId !== '')      $this->printf("    User ID:      %s\n", $this->highlight($userId));
        if ($anonymousId !== '') $this->printf("    Anonymous ID: %s\n", $this->highlight($anonymousId));

        switch ($msgType) {
            case 'track':
                if (isset($msg['event'])) {
                    $this->printf("    Event:        %s\n", $this->success($msg['event']));
                }
                if (!empty($msg['properties'])) {
                    $this->printf("    Properties:\n");
                    $this->printProperties($msg['properties'], 6);
                }
                break;

            case 'identify':
                if (!empty($msg['traits'])) {
                    $this->printf("    Traits:\n");
                    $this->printProperties($msg['traits'], 6);
                }
                break;

            case 'page': case 'screen':
                if (!empty($msg['name'])) {
                    $this->printf("    Name:         %s\n", $this->success($msg['name']));
                }
                if (!empty($msg['properties'])) {
                    $this->printf("    Properties:\n");
                    $this->printProperties($msg['properties'], 6);
                }
                break;

            case 'group':
                if (isset($msg['groupId'])) {
                    $this->printf("    Group ID:     %s\n", $this->success($msg['groupId']));
                }
                if (!empty($msg['traits'])) {
                    $this->printf("    Traits:\n");
                    $this->printProperties($msg['traits'], 6);
                }
                break;

            case 'alias':
                if (isset($msg['previousId'])) {
                    $this->printf("    Previous ID:  %s\n", $this->warn($msg['previousId']));
                }
                break;
        }

        if (isset($msg['timestamp'])) {
            $this->printf("    Timestamp:    %s\n", $this->dim($msg['timestamp']));
        }
    }

    private function printProperties(array $props, int $indent): void
    {
        $prefix = str_repeat(' ', $indent);
        foreach ($props as $k => $v) {
            $this->printf("%s%s: %s\n", $prefix, $this->dim($k), json_encode($v));
        }
    }

    private function printFooter(): void
    {
        $this->println('');
        $this->printLine('═', 60);
        $this->printf("  This is a NOOP dispatcher - no data was sent\n");
        $this->printLine('═', 60);
        $this->println('');
    }

    private function printLine(string $char, int $width): void
    {
        $this->println('  ' . str_repeat($char, $width));
    }

    private function println(string $s): void
    {
        fwrite($this->output, $s . "\n");
    }

    private function printf(string $format, ...$args): void
    {
        fprintf($this->output, $format, ...$args);
    }

    private function errorf(string $format, ...$args): void
    {
        if ($this->config->logger !== null) {
            $this->config->logger->errorf($format, ...$args);
        }
    }

    private function highlight(string $s): string
    {
        return $this->colored ? "\033[1;36m{$s}\033[0m" : $s;
    }

    private function success(string $s): string
    {
        return $this->colored ? "\033[1;32m{$s}\033[0m" : $s;
    }

    private function warn(string $s): string
    {
        return $this->colored ? "\033[1;33m{$s}\033[0m" : $s;
    }

    private function dim(string $s): string
    {
        return $this->colored ? "\033[2m{$s}\033[0m" : $s;
    }

    private function colorType(string $type): string
    {
        if (!$this->colored) return $type;
        $colors = [
            'TRACK'    => "\033[1;35m",
            'IDENTIFY' => "\033[1;34m",
            'PAGE'     => "\033[1;32m",
            'SCREEN'   => "\033[1;32m",
            'GROUP'    => "\033[1;33m",
            'ALIAS'    => "\033[1;31m",
        ];
        $c = $colors[$type] ?? '';
        return $c !== '' ? "{$c}{$type}\033[0m" : $type;
    }
}
