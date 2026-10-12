<?php

namespace Amarenkov\MutableContentTasks\Services;

use Amarenkov\MutableContentTasks\Models\Point;

/**
 * Entry point of the point services: Points::new($type)->...->save(), Points::of($point)->...->update().
 */
class Points
{
    // static
    public static function new(string $type): NewPoint
    {
        return new NewPoint($type);
    }

    public static function of(Point $point): PointActions
    {
        return new PointActions($point);
    }
}
