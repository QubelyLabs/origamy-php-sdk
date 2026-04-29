<?php

declare(strict_types=1);

namespace Origamy;

class StdLogger implements LoggerInterface
{
    public function logf(string $format, mixed ...$args): void
    {
        fprintf(STDERR, 'origamy INFO: ' . $format . "\n", ...$args);
    }

    public function errorf(string $format, mixed ...$args): void
    {
        fprintf(STDERR, 'origamy ERROR: ' . $format . "\n", ...$args);
    }
}
