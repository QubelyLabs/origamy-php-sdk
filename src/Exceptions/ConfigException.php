<?php

declare(strict_types=1);

namespace Origamy\Exceptions;

class ConfigException extends \RuntimeException
{
    public function __construct(
        public readonly string $reason,
        public readonly string $field,
        public readonly mixed $value,
    ) {
        parent::__construct(
            sprintf(
                'analytics.NewWithConfig: %s (analytics.Config.%s: %s)',
                $reason,
                $field,
                var_export($value, true),
            )
        );
    }
}
