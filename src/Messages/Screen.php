<?php

declare(strict_types=1);

namespace Origamy\Messages;

use Origamy\Context;
use Origamy\Exceptions\FieldException;
use Origamy\Integrations;
use Origamy\MessageInterface;
use Origamy\Properties;

class Screen implements MessageInterface
{
    public string $type        = '';
    public string $messageId   = '';
    public string $anonymousId = '';
    public string $userId      = '';
    public string $name        = '';
    public ?\DateTimeImmutable $timestamp    = null;
    public ?Context            $context      = null;
    public ?Properties         $properties   = null;
    public ?Integrations       $integrations = null;

    public function __construct(
        string $userId       = '',
        string $anonymousId  = '',
        string $name         = '',
        string $messageId    = '',
        ?\DateTimeImmutable $timestamp    = null,
        ?Context            $context      = null,
        ?Properties         $properties   = null,
        ?Integrations       $integrations = null
    ) {
        $this->userId       = $userId;
        $this->anonymousId  = $anonymousId;
        $this->name         = $name;
        $this->messageId    = $messageId;
        $this->timestamp    = $timestamp;
        $this->context      = $context;
        $this->properties   = $properties;
        $this->integrations = $integrations;
    }

    public function validate(): void
    {
        if ($this->userId === '' && $this->anonymousId === '') {
            throw new FieldException('analytics.Screen', 'UserId', $this->userId);
        }
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->type !== '')        $data['type']        = $this->type;
        if ($this->messageId !== '')   $data['messageId']   = $this->messageId;
        if ($this->anonymousId !== '') $data['anonymousId'] = $this->anonymousId;
        if ($this->userId !== '')      $data['userId']      = $this->userId;
        if ($this->name !== '')        $data['name']        = $this->name;
        if ($this->timestamp !== null) {
            $data['timestamp'] = Alias::formatTimestamp($this->timestamp);
        }
        if ($this->context !== null && !$this->context->isEmpty()) {
            $data['context'] = $this->context;
        }
        if ($this->properties !== null && count($this->properties) > 0) {
            $data['properties'] = $this->properties;
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
