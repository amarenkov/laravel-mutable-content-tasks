<?php

namespace Amarenkov\MutableContentTasks\Services;

use DomainException;

use Illuminate\Support\Facades\DB;

use Amarenkov\MutableContent\Domain\Log\Changes;

use Amarenkov\MutableContentTasks\Events\ParticipantAdded;
use Amarenkov\MutableContentTasks\Events\ParticipantRemoved;
use Amarenkov\MutableContentTasks\Events\PointDeleted;
use Amarenkov\MutableContentTasks\Events\PointMoved;
use Amarenkov\MutableContentTasks\Events\PointUpdated;
use Amarenkov\MutableContentTasks\Models\Actor;
use Amarenkov\MutableContentTasks\Models\Participant;
use Amarenkov\MutableContentTasks\Models\Point;
use Amarenkov\MutableContentTasks\Services\Concerns\ActsAs;

/**
 * Actions on an existing point.
 */
class PointActions
{
    use ActsAs;

    // public
    public function __construct(
        protected Point $point
    )
    {
    }

    public function update(array $fields): Point
    {
        return DB::transaction(function () use ($fields) {
            $before = $this->point->fields?->toArray() ?? [];

            $this->point->fill($fields);
            $this->prepareLog($this->point);
            $this->point->save();

            $changes = Changes::between($before, $this->point->fields?->toArray() ?? []);

            if (!$changes->isEmpty()) {
                event(new PointUpdated($this->point->id, $this->actor?->id, $changes));
            }

            return $this->point;
        });
    }

    /**
     * Move the point under another parent, or to the top level with null.
     *
     * @throws DomainException when the parent is the point itself or one of its descendants
     */
    public function moveTo(?Point $parent): Point
    {
        for ($ancestor = $parent; $ancestor; $ancestor = $ancestor->parent) {
            if ($ancestor->id === $this->point->id) {
                throw new DomainException(__('mutable-content-tasks::tasks.point_cycle'));
            }
        }

        return DB::transaction(function () use ($parent) {
            $from = $this->point->getField(Point::FIELD_PARENT_ID);

            $this->point->fill([Point::FIELD_PARENT_ID => $parent?->id]);
            $this->prepareLog($this->point);
            $this->point->save();

            event(new PointMoved($this->point->id, $this->actor?->id, $from === null ? null : (int)$from, $parent?->id));

            return $this->point;
        });
    }

    /**
     * @throws DomainException when the point has child points
     */
    public function delete(): void
    {
        if ($this->point->children()->exists()) {
            throw new DomainException(__('mutable-content-tasks::tasks.point_has_children'));
        }

        DB::transaction(function () {
            $this->prepareLog($this->point);
            $this->point->delete();

            event(new PointDeleted($this->point->id, $this->actor?->id));
        });
    }

    public function addParticipant(Actor $participant, string $role): Participant
    {
        return DB::transaction(function () use ($participant, $role) {
            $record = new Participant();
            $record->fill([
                Participant::FIELD_POINT_ID => $this->point->id,
                Participant::FIELD_ACTOR_ID => $participant->id,
                Participant::FIELD_ROLE => $role,
            ]);
            $this->prepareLog($record);
            $record->save();

            event(new ParticipantAdded($this->point->id, $participant->id, $role, $this->actor?->id));

            return $record;
        });
    }

    /**
     * Make the actors the only ones in the role: missing ones are added, the rest removed.
     *
     * @param iterable<Actor> $actors
     */
    public function syncParticipants(string $role, iterable $actors): void
    {
        $wanted = [];

        foreach ($actors as $actor) {
            $wanted[$actor->id] = $actor;
        }

        DB::transaction(function () use ($role, $wanted) {
            $current = Participant::query()
                ->whereField(Participant::FIELD_POINT_ID, $this->point->id)
                ->whereField(Participant::FIELD_ROLE, $role)
                ->get();

            foreach ($current as $record) {
                $actorId = (int)$record->getField(Participant::FIELD_ACTOR_ID);

                if (isset($wanted[$actorId])) {
                    unset($wanted[$actorId]);

                    continue;
                }

                $actor = Actor::query()->find($actorId);

                if ($actor) {
                    $this->removeParticipant($actor, $role);
                }
            }

            foreach ($wanted as $actor) {
                $this->addParticipant($actor, $role);
            }
        });
    }

    /**
     * @throws DomainException when the actor has no such role in the point
     */
    public function removeParticipant(Actor $participant, string $role): void
    {
        $record = Participant::query()
            ->whereField(Participant::FIELD_POINT_ID, $this->point->id)
            ->whereField(Participant::FIELD_ACTOR_ID, $participant->id)
            ->whereField(Participant::FIELD_ROLE, $role)
            ->first();

        if (!$record) {
            throw new DomainException(__('mutable-content-tasks::tasks.participant_absent'));
        }

        DB::transaction(function () use ($record, $participant, $role) {
            $this->prepareLog($record);
            $record->delete();

            event(new ParticipantRemoved($this->point->id, $participant->id, $role, $this->actor?->id));
        });
    }
}
