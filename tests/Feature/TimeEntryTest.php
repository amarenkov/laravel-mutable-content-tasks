<?php

namespace Amarenkov\MutableContentTasks\Tests\Feature;

use Illuminate\Support\Carbon;

use Amarenkov\MutableContentTasks\Lovs\PointType;
use Amarenkov\MutableContentTasks\Models\Actor;
use Amarenkov\MutableContentTasks\Models\Point;
use Amarenkov\MutableContentTasks\Models\TimeEntry;

use Amarenkov\MutableContentTasks\Tests\TestCase;

class TimeEntryTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_start_stops_the_running_entry(): void
    {
        $actor = Actor::forUser($this->user);

        $point = new Point();
        $point->fill([Point::FIELD_TYPE => PointType::TASK, 'description' => 'Work']);
        $point->save();

        Carbon::setTestNow('2026-10-10 10:00:00');
        $first = TimeEntry::start($actor, $point, 'Models');

        $this->assertTrue($first->isRunning());
        $this->assertSame($first->id, TimeEntry::running($actor)->id);

        Carbon::setTestNow('2026-10-10 10:30:00');
        $second = TimeEntry::start($actor);

        $first->refresh();

        $this->assertFalse($first->isRunning());
        $this->assertSame(1800, $first->seconds());
        $this->assertSame($second->id, TimeEntry::running($actor)->id);
        $this->assertNull($second->{TimeEntry::FIELD_POINT_ID});
        $this->assertSame([$first->id], $point->timeEntries()->pluck('id')->all());

        Carbon::setTestNow('2026-10-10 11:00:00');
        TimeEntry::stop($actor);

        $this->assertNull(TimeEntry::running($actor));
        $this->assertSame(1800, $second->fresh()->seconds());
        $this->assertNull(TimeEntry::stop($actor));
        $this->assertSame(2, $actor->timeEntries()->count());
    }
}
