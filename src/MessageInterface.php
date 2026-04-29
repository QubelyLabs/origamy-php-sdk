<?php

declare(strict_types=1);

namespace Origamy;

interface MessageInterface
{
    /**
     * Validate the message fields.
     * Returns null if valid, or throws a FieldException if invalid.
     *
     * @throws \Origamy\Exceptions\FieldException
     */
    public function validate(): void;

    /** Returns the message as an associative array ready for JSON serialization. */
    public function toArray(): array;
}
