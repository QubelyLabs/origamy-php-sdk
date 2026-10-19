<?php

declare(strict_types=1);

namespace Origamy\Exceptions;

use Origamy\SegmentMembersResult;

/**
 * A list segment request the API refused, or that could not be sent.
 *
 * $statusCode is 0 when no response arrived. $errorCode is the API's code
 * when it names one: segment_not_found, segment_not_list, segment_managed,
 * invalid_request or unavailable. When a long list was split, $result holds
 * what the requests before the failing one did.
 */
class SegmentException extends \RuntimeException
{
    private string $apiMessage;

    public function __construct(
        public readonly int $statusCode,
        string $message,
        public readonly ?string $errorCode = null,
        public readonly ?SegmentMembersResult $result = null,
    ) {
        parent::__construct(
            $statusCode > 0
                ? sprintf('segment members: %d%s: %s', $statusCode, $errorCode !== null ? ' ' . $errorCode : '', $message)
                : 'segment members: ' . $message,
            $statusCode,
        );
        $this->apiMessage = $message;
    }

    /** @internal */
    public function withResult(SegmentMembersResult $result): self
    {
        return new self($this->statusCode, $this->apiMessage, $this->errorCode, $result);
    }
}
