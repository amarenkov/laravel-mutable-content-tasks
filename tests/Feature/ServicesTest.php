<?php

namespace Amarenkov\MutableContentTasks\Tests\Feature;

use DomainException;

use Illuminate\Support\Facades\Event;

use Amarenkov\MutableContent\Domain\Log\LogData;
use Amarenkov\MutableContent\Helpers\LogHelper;

use Amarenkov\MutableContentTasks\Events\ParticipantAdded;
use Amarenkov\MutableContentTasks\Events\ParticipantRemoved;
use Amarenkov\MutableContentTasks\Events\PointCreated;
use Amarenkov\MutableContentTasks\Events\PointDeleted;
use Amarenkov\MutableContentTasks\Events\PointMoved;
use Amarenkov\MutableContentTasks\Events\PointUpdated;
use Amarenkov\MutableContentTasks\Lovs\ParticipantRole;
use Amarenkov\MutableContentTasks\Lovs\PointType;
use Amarenkov\MutableContentTasks\Models\Actor;
use Amarenkov\MutableContentTasks\Models\Participant;
use Amarenkov\MutableContentTasks\Models\Point;
use Amarenkov\MutableContentTasks\Services\Points;

use Amarenkov\MutableContentTasks\Tests\Fixtures\Models\User;
use Amarenkov\MutableContentTasks\Tests\TestCase;

class ServicesTest extends TestCase
{
    protected const EVENTS = [
        PointCreated::class,
        PointUpdated::class,
        PointMoved::class,
        PointDeleted::class,
        ParticipantAdded::class,
        ParticipantRemoved::class,
    ];

    protected function otherActor(): Actor
    {
        $user = User::query()->firstOrCreate(['email' => 'executor@example.com'], ['name' => 'Executor', 'password' => 'secret']);

        return Actor::forUser($user);
    }

    public function test_new_point_makes_its_creator_the_author(): void
    {
        Event::fake(self::EVENTS);

        $actor = Actor::forUser($this->user);

        $task = Points::new(PointType::TASK)->by($actor)->fields(['description' => 'Rewrite the app'])->save();
        $part = Points::new(PointType::TASK)->in($task)->fields(['description' => 'Models'])->save();

        $this->assertSame('Rewrite the app', $task->fresh()->{Point::FIELD_EXCERPT});
        $this->assertSame([$part->id], $task->children()->pluck('id')->all());

        $author = $task->participants()->first();

        $this->assertSame($actor->id, (int)$author->{Participant::FIELD_ACTOR_ID});
        $this->assertSame(ParticipantRole::AUTHOR, $author->{Participant::FIELD_ROLE});
        $this->assertSame(0, $part->participants()->count());

        $this->assertSame($this->user->id, (int)LogHelper::query(Point::class)->where(LogHelper::COLUMN_ENTITY_ID, $task->id)->first()->{LogHelper::COLUMN_USER_ID});

        Event::assertDispatched(PointCreated::class, fn (PointCreated $event) => $event->pointId === $task->id && $event->actorId === $actor->id);
        Event::assertDispatched(PointCreated::class, fn (PointCreated $event) => $event->pointId === $part->id && $event->actorId === null);
        Event::assertDispatchedTimes(ParticipantAdded::class, 1);
    }

    public function test_update_reports_only_real_changes(): void
    {
        Event::fake(self::EVENTS);

        $point = Points::new(PointType::TASK)->fields(['description' => 'Draft'])->save();

        Points::of($point)->update(['description' => 'Draft']);

        Event::assertNotDispatched(PointUpdated::class);

        Points::of($point)->by(Actor::forUser($this->user))->logComment('Typo')->update([Point::FIELD_EXCERPT => 'Final']);

        Event::assertDispatched(PointUpdated::class, fn (PointUpdated $event) => !$event->changes->isEmpty() && $event->pointId === $point->id);

        $entry = LogHelper::query(Point::class)
            ->where(LogHelper::COLUMN_ENTITY_ID, $point->id)
            ->orderByDesc(LogHelper::COLUMN_ID)
            ->first();
        $data = LogHelper::decode($entry->{LogHelper::COLUMN_DATA});

        $this->assertSame('Typo', $data[LogData::COMMENT] ?? null);
        $this->assertSame($this->user->id, (int)$entry->{LogHelper::COLUMN_USER_ID});
    }

    public function test_move_refuses_cycles(): void
    {
        Event::fake(self::EVENTS);

        $task = Points::new(PointType::TASK)->save();
        $part = Points::new(PointType::TASK)->in($task)->save();
        $other = Points::new(PointType::TASK)->save();

        Points::of($part)->moveTo($other);

        $this->assertSame($other->id, $part->fresh()->parent->id);
        Event::assertDispatched(PointMoved::class, fn (PointMoved $event) => $event->fromParentId === $task->id && $event->toParentId === $other->id);

        Points::of($part)->moveTo(null);

        $this->assertNull($part->fresh()->parent);

        $this->expectException(DomainException::class);

        Points::of($task)->moveTo(Points::new(PointType::COMMENT)->in($task)->save());
    }

    public function test_delete_refuses_points_with_children(): void
    {
        Event::fake(self::EVENTS);

        $task = Points::new(PointType::TASK)->save();
        $part = Points::new(PointType::TASK)->in($task)->save();

        try {
            Points::of($task)->delete();
            $this->fail('A point with children was deleted');
        } catch (DomainException) {
        }

        Points::of($part)->delete();
        Points::of($task)->delete();

        $this->assertNull(Point::query()->find($task->id));
        Event::assertDispatchedTimes(PointDeleted::class, 2);
    }

    public function test_participants_are_added_and_removed(): void
    {
        Event::fake(self::EVENTS);

        $author = Actor::forUser($this->user);
        $executor = $this->otherActor();

        $task = Points::new(PointType::TASK)->save();

        Points::of($task)->by($author)->addParticipant($executor, ParticipantRole::EXECUTOR);

        Event::assertDispatched(ParticipantAdded::class, fn (ParticipantAdded $event) => $event->participantActorId === $executor->id && $event->actorId === $author->id);

        Points::of($task)->by($author)->removeParticipant($executor, ParticipantRole::EXECUTOR);

        $this->assertSame(0, $task->participants()->count());
        Event::assertDispatched(ParticipantRemoved::class, fn (ParticipantRemoved $event) => $event->role === ParticipantRole::EXECUTOR);

        Points::of($task)->addParticipant($executor, ParticipantRole::EXECUTOR);

        $this->assertSame(1, $task->participants()->count());

        $this->expectException(DomainException::class);

        Points::of($task)->removeParticipant($executor, ParticipantRole::AUTHOR);
    }
}
