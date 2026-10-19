<?php

declare(strict_types=1);

namespace Origamy;

/**
 * The outcome of a list segment membership change.
 *
 * $added counts rows queued for adding: the people appear in the segment once
 * the event pipeline has processed them, normally within seconds. $notFound
 * counts removals that matched nobody in the segment. Each $rejected row is
 * ['list' => 'add'|'remove', 'index' => int, 'error' => string], where index
 * is the row's position in the array passed to addMembers/removeMembers.
 */
class SegmentMembersResult
{
    /** @param array<int, array{list: string, index: int, error: string}> $rejected */
    public function __construct(
        public readonly int $segmentId,
        public int $added = 0,
        public int $removed = 0,
        public int $notFound = 0,
        public array $rejected = [],
    ) {}
}
