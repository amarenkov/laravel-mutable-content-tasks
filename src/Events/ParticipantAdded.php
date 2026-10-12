<?php

namespace Amarenkov\MutableContentTasks\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An actor took a role in a point.
 */
final readonly class ParticipantAdded implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    // public
    public function __construct(
        public int $pointId,
        public int $participantActorId,
        public string $role,
        public ?int $actorId,
    )
    {
    }
}
