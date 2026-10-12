<?php

namespace Amarenkov\MutableContentTasks\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An actor stopped working.
 */
final readonly class TimeEntryStopped implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    // public
    public function __construct(
        public int $timeEntryId,
        public int $actorId,
        public ?int $pointId,
        public int $seconds,
    )
    {
    }
}
