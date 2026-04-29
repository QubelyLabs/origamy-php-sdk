<?php

declare(strict_types=1);

namespace Origamy\Messages;

use Origamy\Context;
use Origamy\Exceptions\FieldException;
use Origamy\Integrations;
use Origamy\MessageInterface;
use Origamy\Traits as OrigamyTraits;

class Identify implements MessageInterface
{
    public string $type        = '';
    public string $messageId   = '';
    public string $anonymousId = '';
    public string $userId      = '';
    public ?\DateTimeImmutable $timestamp    = null;
    public ?Context            $context      = null;
    public ?OrigamyTraits      $traits       = null;
    public ?Integrations       $integrations = null;

    public function __construct(
        string $userId       = '',
        string $anonymousId  = '',
        string $messageId    = '',
        ?\DateTimeImmutable  $timestamp    = null,
        ?Context             $context      = null,
        ?OrigamyTraits       $traits       = null,
        ?Integrations        $integrations = null,
    ) {
        $this->userId       = $userId;
        $this->anonymousId  = $anonymousId;
        $this->messageId    = $messageId;
        $this->timestamp    = $timestamp;
        $this->context      = $context;
        $this->traits       = $traits;
        $this->integrations = $integrations;
    }

    public function validate(): void
    {
        if ($this->userId === '' && $this->anonymousId === '') {
            throw new FieldException('analytics.Identify', 'UserId', $this->userId);
        }
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->type !== '')        $data['type']        = $this->type;
        if ($this->messageId !== '')   $data['messageId']   = $this->messageId;
        if ($this->anonymousId !== '') $data['anonymousId'] = $this->anonymousId;
        if ($this->userId !== '')      $data['userId']      = $this->userId;
        if ($this->timestamp !== null) {
            $data['timestamp'] = Alias::formatTimestamp($this->timestamp);
        }
        if ($this->context !== null && !$this->context->isEmpty()) {
            $data['context'] = $this->context;
        }
        if ($this->traits !== null && count($this->traits) > 0) {
            $data['traits'] = $this->traits;
        }
        if ($this->integrations !== null && count($this->integrations) > 0) {
            $data['integrations'] = $this->integrations;
        }
        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
