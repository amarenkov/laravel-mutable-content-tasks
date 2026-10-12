<?php

namespace Amarenkov\MutableContentTasks\Support;

use Illuminate\Support\Number;

/**
 * Length in seconds for people.
 */
class Duration
{
    // static
    /**
     * Hours, minutes and seconds: 01:05:09.
     */
    public static function clock(int $seconds): string
    {
        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }

    /**
     * Hours with one decimal in the application locale, 0.1 h at least: "1.5 h", "1,5 ч.".
     */
    public static function hours(int $seconds): string
    {
        $hours = Number::format(max($seconds / 3600, 0.1), precision: 1, locale: app()->getLocale());

        return __('mutable-content-tasks::tasks.hours', ['hours' => $hours]);
    }
}
