<?php

namespace Amarenkov\MutableContentTasks\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

use Amarenkov\MutableContent\Domain\Log\Changes;

/**
 * A time entry was corrected; previousPointId differs from pointId when it was moved to another point.
 */
final readonly class TimeEntryUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    // public
    public function __construct(
        public int $timeEntryId,
        public ?int $actorId,
        public ?int $pointId,
        public ?int $previousPointId,
        public Changes $changes,
    )
    {
    }
}
