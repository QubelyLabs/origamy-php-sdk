<?php

declare(strict_types=1);

namespace Origamy;

interface LoggerInterface
{
    /** Log an informational message. */
    public function logf(string $format, ...$args): void;

    /** Log an error message. */
    public function errorf(string $format, ...$args): void;
}
