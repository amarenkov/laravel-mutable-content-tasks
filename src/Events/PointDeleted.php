<?php

namespace Amarenkov\MutableContentTasks\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A point was deleted.
 */
final readonly class PointDeleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    // public
    public function __construct(
        public int $pointId,
        public ?int $actorId,
    )
    {
    }
}
