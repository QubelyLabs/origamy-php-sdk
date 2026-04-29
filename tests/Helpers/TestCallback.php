<?php

declare(strict_types=1);

namespace Origamy\Tests\Helpers;

use Origamy\CallbackInterface;
use Origamy\MessageInterface;

class TestCallback implements CallbackInterface
{
    /** @var callable|null */
    private $onSuccess;
    /** @var callable|null */
    private $onFailure;

    public function __construct(?callable $onSuccess = null, ?callable $onFailure = null)
    {
        $this->onSuccess = $onSuccess;
        $this->onFailure = $onFailure;
    }

    public function success(MessageInterface $message): void
    {
        if ($this->onSuccess !== null) {
            ($this->onSuccess)($message);
        }
    }

    public function failure(MessageInterface $message, \Throwable $error): void
    {
        if ($this->onFailure !== null) {
            ($this->onFailure)($message, $error);
        }
    }
}
