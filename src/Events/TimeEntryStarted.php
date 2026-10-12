<?php

namespace Amarenkov\MutableContentTasks\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An actor started working, on a point or on nothing in particular.
 */
final readonly class TimeEntryStarted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    // public
    public function __construct(
        public int $timeEntryId,
        public int $actorId,
        public ?int $pointId,
    )
    {
    }
}
