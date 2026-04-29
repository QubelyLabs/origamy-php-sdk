<?php

declare(strict_types=1);

namespace Origamy\Exceptions;

class FieldException extends \RuntimeException
{
    /** @var string */
    public $type;
    /** @var string */
    public $name;
    /** @var mixed */
    public $value;

    /**
     * @param mixed $value
     */
    public function __construct(string $type, string $name, $value)
    {
        $this->type  = $type;
        $this->name  = $name;
        $this->value = $value;

        parent::__construct(
            sprintf(
                '%s.%s: invalid field value: %s',
                $type,
                $name,
                var_export($value, true)
            )
        );
    }
}
