<?php

namespace Amarenkov\MutableContentTasks\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A point was moved to another parent.
 */
final readonly class PointMoved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    // public
    public function __construct(
        public int $pointId,
        public ?int $actorId,
        public ?int $fromParentId,
        public ?int $toParentId,
    )
    {
    }
}
