<?php

declare(strict_types=1);

namespace Origamy\Exceptions;

class FieldException extends \RuntimeException
{
    public function __construct(
        public readonly string $type,
        public readonly string $name,
        public readonly mixed $value,
    ) {
        parent::__construct(
            sprintf(
                '%s.%s: invalid field value: %s',
                $type,
                $name,
                var_export($value, true),
            )
        );
    }
}
