<?php

namespace Amarenkov\MutableContentTasks\Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use Amarenkov\MutableContentTasks\Events\TimeEntryStarted;
use Amarenkov\MutableContentTasks\Events\TimeEntryStopped;
use Amarenkov\MutableContentTasks\Lovs\PointType;
use Amarenkov\MutableContentTasks\Models\Actor;
use Amarenkov\MutableContentTasks\Models\Point;
use Amarenkov\MutableContentTasks\Models\TimeEntry;
use Amarenkov\MutableContentTasks\Services\Time;

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
        Event::fake([TimeEntryStarted::class, TimeEntryStopped::class]);

        $actor = Actor::forUser($this->user);

        $point = new Point();
        $point->fill([Point::FIELD_TYPE => PointType::TASK, 'description' => 'Work']);
        $point->save();

        Carbon::setTestNow('2026-10-10 10:00:00');
        $first = Time::of($actor)->on($point)->comment('Models')->start();

        $this->assertTrue($first->isRunning());
        $this->assertSame($first->id, TimeEntry::running($actor)->id);

        Carbon::setTestNow('2026-10-10 10:30:00');
        $second = Time::of($actor)->start();

        $first->refresh();

        $this->assertFalse($first->isRunning());
        $this->assertSame(1800, $first->seconds());
        $this->assertSame($second->id, TimeEntry::running($actor)->id);
        $this->assertNull($second->{TimeEntry::FIELD_POINT_ID});
        $this->assertSame([$first->id], $point->timeEntries()->pluck('id')->all());

        Carbon::setTestNow('2026-10-10 11:00:00');
        Time::of($actor)->stop();

        $this->assertNull(TimeEntry::running($actor));
        $this->assertSame(1800, $second->fresh()->seconds());
        $this->assertNull(Time::of($actor)->stop());
        $this->assertSame(2, $actor->timeEntries()->count());
        $this->assertSame('Models', $first->{TimeEntry::FIELD_COMMENT});

        Event::assertDispatchedTimes(TimeEntryStarted::class, 2);
        Event::assertDispatched(TimeEntryStopped::class, fn (TimeEntryStopped $event) => $event->timeEntryId === $first->id && $event->pointId === $point->id && $event->seconds === 1800);
        Event::assertDispatchedTimes(TimeEntryStopped::class, 2);
    }
}
