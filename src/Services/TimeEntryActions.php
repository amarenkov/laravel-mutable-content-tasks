<?php

namespace Amarenkov\MutableContentTasks\Services;

use DomainException;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use Amarenkov\MutableContent\Domain\Log\Changes;

use Amarenkov\MutableContentTasks\Events\TimeEntryDeleted;
use Amarenkov\MutableContentTasks\Events\TimeEntryUpdated;
use Amarenkov\MutableContentTasks\Models\TimeEntry;
use Amarenkov\MutableContentTasks\Services\Concerns\ActsAs;

/**
 * Corrections of an existing time entry.
 */
class TimeEntryActions
{
    use ActsAs;

    // public
    public function __construct(
        protected TimeEntry $entry
    )
    {
    }

    /**
     * Change from, to, point or comment; the actor of the entry stays. A finished entry cannot be made running again.
     *
     * @throws DomainException when the period is empty, lies in the future or overlaps another entry of the actor
     */
    public function update(array $fields): TimeEntry
    {
        unset($fields[TimeEntry::FIELD_ACTOR_ID]);

        return DB::transaction(function () use ($fields) {
            $before = $this->entry->fields?->toArray() ?? [];
            $wasRunning = $this->entry->isRunning();
            $previousPointId = $this->entry->pointId();

            $this->entry->fill($fields);
            $this->check($wasRunning);
            $this->prepareLog($this->entry);
            $this->entry->save();

            $changes = Changes::between($before, $this->entry->fields?->toArray() ?? []);

            if (!$changes->isEmpty()) {
                event(new TimeEntryUpdated($this->entry->id, $this->actor?->id, $this->entry->pointId(), $previousPointId, $changes));
            }

            return $this->entry;
        });
    }

    public function delete(): void
    {
        DB::transaction(function () {
            $id = $this->entry->id;
            $pointId = $this->entry->pointId();

            $this->prepareLog($this->entry);
            $this->entry->delete();

            event(new TimeEntryDeleted($id, $this->actor?->id, $pointId));
        });
    }

    // protected
    protected function check(bool $wasRunning): void
    {
        $from = $this->entry->getDateTime(TimeEntry::FIELD_FROM);
        $to = $this->entry->getDateTime(TimeEntry::FIELD_TO);
        $now = Carbon::now();

        if (!$from) {
            throw new DomainException(__('mutable-content-tasks::tasks.time_from_required'));
        }

        if (!$to && !$wasRunning) {
            throw new DomainException(__('mutable-content-tasks::tasks.time_to_required'));
        }

        if ($from > $now || ($to && $to > $now)) {
            throw new DomainException(__('mutable-content-tasks::tasks.time_in_future'));
        }

        if ($to && $to <= $from) {
            throw new DomainException(__('mutable-content-tasks::tasks.time_period_empty'));
        }

        $overlaps = TimeEntry::query()
            ->whereKeyNot($this->entry->id)
            ->whereField(TimeEntry::FIELD_ACTOR_ID, $this->entry->getField(TimeEntry::FIELD_ACTOR_ID))
            ->when($to, fn ($query) => $query->whereField(TimeEntry::FIELD_FROM, '<', $to))
            ->where(fn ($query) => $query->whereField(TimeEntry::FIELD_TO, null)->orWhereField(TimeEntry::FIELD_TO, '>', $from))
            ->exists();

        if ($overlaps) {
            throw new DomainException(__('mutable-content-tasks::tasks.time_overlap'));
        }
    }
}
