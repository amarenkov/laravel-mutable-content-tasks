<?php

namespace Amarenkov\MutableContentTasks\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A closed point was opened again.
 */
final readonly class PointReopened implements ShouldDispatchAfterCommit
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
