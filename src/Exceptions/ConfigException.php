<?php

declare(strict_types=1);

namespace Origamy\Exceptions;

class ConfigException extends \RuntimeException
{
    /** @var string */
    public $reason;
    /** @var string */
    public $field;
    /** @var mixed */
    public $value;

    /**
     * @param mixed $value
     */
    public function __construct(string $reason, string $field, $value)
    {
        $this->reason = $reason;
        $this->field  = $field;
        $this->value  = $value;

        parent::__construct(
            sprintf(
                'analytics.NewWithConfig: %s (analytics.Config.%s: %s)',
                $reason,
                $field,
                var_export($value, true)
            )
        );
    }
}
