<?php

namespace Amarenkov\MutableContentTasks\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

use Amarenkov\MutableContent\Domain\Log\Changes;

/**
 * Fields of a point were changed.
 */
final readonly class PointUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    // public
    public function __construct(
        public int $pointId,
        public ?int $actorId,
        public Changes $changes,
    )
    {
    }
}
