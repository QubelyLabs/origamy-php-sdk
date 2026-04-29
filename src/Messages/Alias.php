<?php

declare(strict_types=1);

namespace Origamy\Messages;

use Origamy\Context;
use Origamy\Exceptions\FieldException;
use Origamy\Integrations;
use Origamy\MessageInterface;

class Alias implements MessageInterface
{
    public string $type         = '';
    public string $messageId    = '';
    public string $previousId;
    public string $userId;
    public ?\DateTimeImmutable $timestamp    = null;
    public ?Context            $context      = null;
    public ?Integrations       $integrations = null;

    public function __construct(
        string $previousId,
        string $userId,
        string $messageId    = '',
        ?\DateTimeImmutable $timestamp    = null,
        ?Context            $context      = null,
        ?Integrations       $integrations = null
    ) {
        $this->previousId   = $previousId;
        $this->userId       = $userId;
        $this->messageId    = $messageId;
        $this->timestamp    = $timestamp;
        $this->context      = $context;
        $this->integrations = $integrations;
    }

    public function validate(): void
    {
        if ($this->userId === '') {
            throw new FieldException('analytics.Alias', 'UserId', $this->userId);
        }
        if ($this->previousId === '') {
            throw new FieldException('analytics.Alias', 'PreviousId', $this->previousId);
        }
    }

    public function toArray(): array
    {
        $data = [];
        if ($this->type !== '')      $data['type']      = $this->type;
        if ($this->messageId !== '') $data['messageId'] = $this->messageId;
        $data['previousId'] = $this->previousId;
        $data['userId']     = $this->userId;
        if ($this->timestamp !== null) {
            $data['timestamp'] = self::formatTimestamp($this->timestamp);
        }
        if ($this->context !== null && !$this->context->isEmpty()) {
            $data['context'] = $this->context;
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

    public static function formatTimestamp(\DateTimeImmutable $ts): string
    {
        $utc = $ts->setTimezone(new \DateTimeZone('UTC'));
        $ms  = (int) $utc->format('v');
        if ($ms === 0) {
            return $utc->format('Y-m-d\TH:i:s\Z');
        }
        return $utc->format('Y-m-d\TH:i:s.') . sprintf('%03d', $ms) . 'Z';
    }
}
