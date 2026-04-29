<?php

declare(strict_types=1);

namespace Origamy;

interface CallbackInterface
{
    /** Called for every message that was successfully sent to the API. */
    public function success(MessageInterface $message): void;

    /** Called for every message that failed to be sent and will be discarded. */
    public function failure(MessageInterface $message, \Throwable $error): void;
}
