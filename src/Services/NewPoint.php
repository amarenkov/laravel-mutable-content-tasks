<?php

namespace Amarenkov\MutableContentTasks\Services;

use Illuminate\Support\Facades\DB;

use Amarenkov\MutableContentTasks\Events\ParticipantAdded;
use Amarenkov\MutableContentTasks\Events\PointCreated;
use Amarenkov\MutableContentTasks\Lovs\ParticipantRole;
use Amarenkov\MutableContentTasks\Models\Participant;
use Amarenkov\MutableContentTasks\Models\Point;
use Amarenkov\MutableContentTasks\Services\Concerns\ActsAs;

/**
 * A point being created; the actor doing it becomes its author.
 */
class NewPoint
{
    use ActsAs;

    // protected
    protected ?Point $parent = null;

    protected array $fields = [];

    // public
    public function __construct(
        protected string $type
    )
    {
    }

    public function in(?Point $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function fields(array $fields): static
    {
        $this->fields = $fields + $this->fields;

        return $this;
    }

    public function save(): Point
    {
        return DB::transaction(function () {
            $point = new Point();
            $point->fill([Point::FIELD_TYPE => $this->type, Point::FIELD_PARENT_ID => $this->parent?->id] + $this->fields);
            $this->prepareLog($point);
            $point->save();

            event(new PointCreated($point->id, $this->actor?->id));

            if ($this->actor) {
                $participant = new Participant();
                $participant->fill([
                    Participant::FIELD_POINT_ID => $point->id,
                    Participant::FIELD_ACTOR_ID => $this->actor->id,
                    Participant::FIELD_ROLE => ParticipantRole::AUTHOR,
                ]);
                $this->prepareLog($participant);
                $participant->save();

                event(new ParticipantAdded($point->id, $this->actor->id, ParticipantRole::AUTHOR, $this->actor->id));
            }

            return $point;
        });
    }
}
