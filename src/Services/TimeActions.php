<?php

namespace Amarenkov\MutableContentTasks\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use Amarenkov\MutableContentTasks\Events\TimeEntryStarted;
use Amarenkov\MutableContentTasks\Events\TimeEntryStopped;
use Amarenkov\MutableContentTasks\Models\Actor;
use Amarenkov\MutableContentTasks\Models\Point;
use Amarenkov\MutableContentTasks\Models\TimeEntry;
use Amarenkov\MutableContentTasks\Services\Concerns\ActsAs;

/**
 * Time tracking of an actor; by default the actor itself does it.
 */
class TimeActions
{
    use ActsAs;

    // protected
    protected ?Point $point = null;

    protected ?string $comment = null;

    // public
    public function __construct(
        protected Actor $worker
    )
    {
        $this->actor = $worker;
    }

    public function on(?Point $point): static
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Comment of the time entry itself.
     */
    public function comment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Start an entry now, stopping the running one; an entry already running on the same point goes on.
     */
    public function start(): TimeEntry
    {
        return DB::transaction(function () {
            $running = TimeEntry::running($this->worker);

            if ($running && $running->pointId() === $this->point?->id) {
                return $running;
            }

            $now = Carbon::now();

            $this->stopAt($now);

            $entry = new TimeEntry();
            $entry->fill([
                TimeEntry::FIELD_ACTOR_ID => $this->worker->id,
                TimeEntry::FIELD_FROM => $now,
                TimeEntry::FIELD_POINT_ID => $this->point?->id,
                TimeEntry::FIELD_COMMENT => $this->comment,
            ]);
            $this->prepareLog($entry);
            $entry->save();

            event(new TimeEntryStarted($entry->id, $this->worker->id, $this->point?->id));

            return $entry;
        });
    }

    /**
     * Stop the running entry; null if nothing is running.
     */
    public function stop(): ?TimeEntry
    {
        return DB::transaction(fn () => $this->stopAt(Carbon::now()));
    }

    // protected
    protected function stopAt(Carbon $at): ?TimeEntry
    {
        $entry = TimeEntry::running($this->worker);

        if (!$entry) {
            return null;
        }

        $entry->{TimeEntry::FIELD_TO} = $at;
        $this->prepareLog($entry);
        $entry->save();

        event(new TimeEntryStopped($entry->id, $this->worker->id, $entry->pointId(), $entry->seconds()));

        return $entry;
    }
}
