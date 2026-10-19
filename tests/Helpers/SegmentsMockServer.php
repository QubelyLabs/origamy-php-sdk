<?php

declare(strict_types=1);

namespace Origamy\Tests\Helpers;

/**
 * Runs segments_mock_server.php under PHP's built-in server: scripted
 * responses in, every request captured.
 */
class SegmentsMockServer
{
    private mixed $process = null;
    private int $port = 0;
    private string $captureFile = '';
    private string $responsesFile = '';

    /** @param list<array<string, mixed>> $responses see segments_mock_server.php */
    public function start(array $responses = [['echo' => true]]): void
    {
        $this->port          = $this->findFreePort();
        $this->captureFile   = (string) tempnam(sys_get_temp_dir(), 'origamy_seg_cap_');
        $this->responsesFile = (string) tempnam(sys_get_temp_dir(), 'origamy_seg_res_');
        file_put_contents($this->captureFile, '');
        file_put_contents($this->responsesFile, json_encode($responses));

        $this->process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $this->port, __DIR__ . '/segments_mock_server.php'],
            [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            null,
            [
                'ORIGAMY_CAPTURE_FILE'   => $this->captureFile,
                'ORIGAMY_RESPONSES_FILE' => $this->responsesFile,
            ],
        );
        if ($this->process === false) {
            throw new \RuntimeException('Failed to start mock HTTP server');
        }
        $this->waitReady();
    }

    public function getUrl(): string
    {
        return 'http://127.0.0.1:' . $this->port;
    }

    /** @return list<array<string, mixed>> every request so far, oldest first */
    public function requests(): array
    {
        clearstatcache(true, $this->captureFile);
        $lines = file($this->captureFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        return array_map(static fn (string $l) => json_decode($l, true), $lines);
    }

    public function stop(): void
    {
        if ($this->process !== null) {
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }
        foreach ([$this->captureFile, $this->responsesFile] as $f) {
            if ($f !== '' && file_exists($f)) {
                @unlink($f);
            }
        }
    }

    private function waitReady(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $sock = @fsockopen('127.0.0.1', $this->port, $errno, $errstr, 0.05);
            if ($sock !== false) {
                fclose($sock);
                return;
            }
            usleep(50_000);
        }
        throw new \RuntimeException("Mock server failed to start on port {$this->port}");
    }

    private function findFreePort(): int
    {
        $sock = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($sock === false) {
            throw new \RuntimeException("Cannot find free port: $errstr");
        }
        $name = (string) stream_socket_get_name($sock, false);
        fclose($sock);
        return (int) substr($name, strrpos($name, ':') + 1);
    }
}
