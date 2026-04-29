<?php

declare(strict_types=1);

namespace Origamy\Tests\Helpers;

/**
 * Manages a PHP built-in server subprocess for HTTP integration tests.
 */
class MockHttpServer
{
    private mixed $process = null;
    private int   $port    = 0;
    private string $tmpFile = '';

    public function start(): void
    {
        $this->port    = $this->findFreePort();
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'origamy_cap_');
        file_put_contents($this->tmpFile, '');

        $script = __DIR__ . '/mock_server.php';
        $php    = PHP_BINARY;

        $this->process = proc_open(
            sprintf('%s -S localhost:%d %s', $php, $this->port, escapeshellarg($script)),
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            ['ORIGAMY_CAPTURE_FILE' => $this->tmpFile],
        );

        if ($this->process === false) {
            throw new \RuntimeException('Failed to start mock HTTP server');
        }

        $this->waitReady();
    }

    public function getUrl(): string
    {
        return "http://localhost:{$this->port}";
    }

    /** Read the last captured request (polls until data appears or timeout). */
    public function readRequest(float $timeoutSec = 5.0): array
    {
        $deadline = microtime(true) + $timeoutSec;
        while (microtime(true) < $deadline) {
            clearstatcache(true, $this->tmpFile);
            if (file_exists($this->tmpFile) && filesize($this->tmpFile) > 2) {
                $data = json_decode((string) file_get_contents($this->tmpFile), true);
                if (is_array($data)) {
                    return $data;
                }
            }
            usleep(20_000);
        }
        throw new \RuntimeException('Timed out waiting for mock server request');
    }

    /** Clear the capture file so readRequest() blocks until the next request arrives. */
    public function reset(): void
    {
        file_put_contents($this->tmpFile, '');
    }

    public function stop(): void
    {
        if ($this->process !== null) {
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }
        if ($this->tmpFile !== '' && file_exists($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
    }

    private function waitReady(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $sock = @fsockopen('localhost', $this->port, $errno, $errstr, 0.05);
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
