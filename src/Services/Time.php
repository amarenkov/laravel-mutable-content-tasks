<?php

namespace Amarenkov\MutableContentTasks\Services;

use Amarenkov\MutableContentTasks\Models\Actor;

/**
 * Entry point of the time tracking services: Time::of($actor)->on($point)->start(), Time::of($actor)->stop().
 */
class Time
{
    // static
    public static function of(Actor $actor): TimeActions
    {
        return new TimeActions($actor);
    }
}
