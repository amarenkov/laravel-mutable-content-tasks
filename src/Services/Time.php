<?php

namespace Amarenkov\MutableContentTasks\Services;

use Amarenkov\MutableContentTasks\Models\Actor;
use Amarenkov\MutableContentTasks\Models\TimeEntry;

/**
 * Entry point of the time tracking services: Time::of($actor)->on($point)->start(), Time::of($actor)->stop(),
 * Time::entry($entry)->update([...])/delete().
 */
class Time
{
    // static
    public static function of(Actor $actor): TimeActions
    {
        return new TimeActions($actor);
    }

    public static function entry(TimeEntry $entry): TimeEntryActions
    {
        return new TimeEntryActions($entry);
    }
}
