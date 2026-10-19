<?php

namespace Amarenkov\MutableContentTasks\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A time entry was deleted.
 */
final readonly class TimeEntryDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    // public
    public function __construct(
        public int $timeEntryId,
        public ?int $actorId,
        public ?int $pointId,
    )
    {
    }
}
