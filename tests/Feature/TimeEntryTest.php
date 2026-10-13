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

    protected function makeTask(string $description): Point
    {
        $point = new Point();
        $point->fill([Point::FIELD_TYPE => PointType::TASK, 'description' => $description]);
        $point->save();

        return $point;
    }

    public function test_start_on_the_running_point_goes_on(): void
    {
        Event::fake([TimeEntryStarted::class, TimeEntryStopped::class]);

        $actor = Actor::forUser($this->user);
        $point = $this->makeTask('Work');

        Carbon::setTestNow('2026-10-10 10:00:00');
        $first = Time::of($actor)->on($point)->start();

        Carbon::setTestNow('2026-10-10 10:10:00');
        $again = Time::of($actor)->on($point)->start();

        $this->assertSame($first->id, $again->id);
        $this->assertTrue($again->isRunning());
        $this->assertSame(1, $actor->timeEntries()->count());
        Event::assertDispatchedTimes(TimeEntryStarted::class, 1);
        Event::assertNotDispatched(TimeEntryStopped::class);

        Time::of($actor)->stop();
        Time::of($actor)->start();
        $idle = Time::of($actor)->start();

        $this->assertNull($idle->pointId());
        $this->assertSame(2, $actor->timeEntries()->count());
    }

    public function test_seconds_on_a_point_and_report_for_a_period(): void
    {
        $actor = Actor::forUser($this->user);
        $models = $this->makeTask('Models');
        $screens = $this->makeTask('Screens');

        Carbon::setTestNow('2026-10-09 23:00:00');
        Time::of($actor)->on($models)->start();

        Carbon::setTestNow('2026-10-10 01:00:00');
        Time::of($actor)->on($screens)->start();

        Carbon::setTestNow('2026-10-10 01:30:00');
        Time::of($actor)->start();

        Carbon::setTestNow('2026-10-10 02:00:00');
        Time::of($actor)->on($models)->start();

        Carbon::setTestNow('2026-10-10 03:00:00');

        $this->assertSame(3 * 3600, TimeEntry::secondsOn($actor, $models));
        $this->assertSame(1800, TimeEntry::secondsOn($actor, $screens));
        $this->assertSame([$models->id => 3 * 3600, $screens->id => 1800, 999999 => 0], TimeEntry::secondsByPoint($actor, [$models->id, $screens->id, 999999]));
        $this->assertSame([], TimeEntry::secondsByPoint($actor, []));

        $day = Carbon::parse('2026-10-10');

        $this->assertSame(
            [$models->id => 2 * 3600, $screens->id => 1800, '' => 1800],
            TimeEntry::report($actor, $day->copy()->startOfDay(), $day->copy()->endOfDay())
        );
        $this->assertSame(
            [$models->id => 3600],
            TimeEntry::report($actor, Carbon::parse('2026-10-09 00:00:00'), Carbon::parse('2026-10-09 23:59:59')->addSecond())
        );
        $this->assertSame([], TimeEntry::report(Actor::forUser($this->user), Carbon::parse('2026-10-11'), Carbon::parse('2026-10-12')));
    }

    public function test_last_entry_of_the_actor(): void
    {
        $actor = Actor::forUser($this->user);
        $point = $this->makeTask('Work');

        $this->assertNull(TimeEntry::last($actor));

        Carbon::setTestNow('2026-10-10 10:00:00');
        $onPoint = Time::of($actor)->on($point)->start();

        Carbon::setTestNow('2026-10-10 11:00:00');
        $idle = Time::of($actor)->start();

        Carbon::setTestNow('2026-10-10 12:00:00');
        Time::of($actor)->stop();

        $this->assertSame($idle->id, TimeEntry::last($actor)->id);
        $this->assertSame($onPoint->id, TimeEntry::last($actor, onPoint: true)->id);
    }
}
