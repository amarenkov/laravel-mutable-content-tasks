<?php

namespace Amarenkov\MutableContentTasks\Tests\Feature;

use DomainException;

use Illuminate\Database\UniqueConstraintViolationException;

use Amarenkov\MutableContent\Models\Field\Field;

use Amarenkov\MutableContentTasks\Lovs\ActorType;
use Amarenkov\MutableContentTasks\Lovs\ParticipantRole;
use Amarenkov\MutableContentTasks\Lovs\PointType;
use Amarenkov\MutableContentTasks\Models\Actor;
use Amarenkov\MutableContentTasks\Models\Participant;
use Amarenkov\MutableContentTasks\Models\Point;

use Amarenkov\MutableContentTasks\Tests\TestCase;

class ModelsTest extends TestCase
{
    protected function makePoint(string $type, array $fields = []): Point
    {
        if (!isset($fields[Point::FIELD_EXCERPT]) && !isset($fields['description'])) {
            $fields[Point::FIELD_EXCERPT] = 'Point';
        }

        $point = new Point();
        $point->fill([Point::FIELD_TYPE => $type] + $fields);
        $point->save();

        return $point;
    }

    public function test_point_types_are_a_lov(): void
    {
        $types = Point::getTypeOptions();

        $this->assertSame('Task', $types[PointType::TASK]);
        $this->assertSame('Comment', $types[PointType::COMMENT]);
        $this->assertSame(PointType::TASK, $this->makePoint(PointType::TASK)->typeCode());
    }

    public function test_points_form_a_tree(): void
    {
        $task = $this->makePoint(PointType::TASK, ['description' => 'Rewrite the app']);
        $part = $this->makePoint(PointType::TASK, [Point::FIELD_PARENT_ID => $task->id, 'description' => 'Models']);
        $comment = $this->makePoint(PointType::COMMENT, [Point::FIELD_PARENT_ID => $part->id, 'description' => 'Start with Point']);
        $reply = $this->makePoint(PointType::COMMENT, [Point::FIELD_PARENT_ID => $comment->id, 'description' => 'Agreed']);

        $this->assertSame([$part->id], $task->children()->pluck('id')->all());
        $this->assertSame($comment->id, $reply->parent->id);
        $this->assertSame($task->id, $part->fresh()->parent->id);
    }

    public function test_excerpt_is_taken_from_the_description(): void
    {
        $point = $this->makePoint(PointType::TASK, ['description' => "First line\nsecond line"]);

        $this->assertSame("First line\nsecond line", $point->fresh()->{Point::FIELD_EXCERPT});
        $this->assertSame('First linesecond lin...', $point->oneLineExcerpt());

        $long = $this->makePoint(PointType::TASK, ['description' => str_repeat('a', 300)]);

        $this->assertSame(str_repeat('a', 250).'...', $long->fresh()->{Point::FIELD_EXCERPT});

        $explicit = $this->makePoint(PointType::TASK, [Point::FIELD_EXCERPT => 'Short', 'description' => 'Long description']);

        $this->assertSame('Short', $explicit->fresh()->{Point::FIELD_EXCERPT});
    }

    public function test_excerpt_follows_the_description_until_changed_by_hand(): void
    {
        $point = $this->makePoint(PointType::TASK, ['description' => 'Draft']);

        $point->fill(['description' => 'Rewrite the app'])->save();

        $this->assertSame('Rewrite the app', $point->fresh()->{Point::FIELD_EXCERPT});

        $point->fill([Point::FIELD_EXCERPT => 'Rewrite'])->save();
        $point->fill(['description' => 'Rewrite the app from scratch'])->save();

        $this->assertSame('Rewrite', $point->fresh()->{Point::FIELD_EXCERPT});

        $point->fill([Point::FIELD_EXCERPT => null])->save();

        $this->assertSame('Rewrite the app from scratch', $point->fresh()->{Point::FIELD_EXCERPT});

        $point->fill(['description' => null])->save();

        $this->assertSame('Rewrite the app from scratch', $point->fresh()->{Point::FIELD_EXCERPT});

        $this->expectException(DomainException::class);

        $point->fill([Point::FIELD_EXCERPT => null])->save();
    }

    public function test_point_needs_an_excerpt_or_a_description(): void
    {
        $this->expectException(DomainException::class);

        $point = new Point();
        $point->fill([Point::FIELD_TYPE => PointType::TASK]);
        $point->save();
    }

    public function test_actor_of_a_user_is_created_once(): void
    {
        $actor = Actor::forUser($this->user);

        $this->assertSame(ActorType::USER, $actor->typeCode());
        $this->assertSame($this->user->id, $actor->{Actor::FIELD_USER_ID});
        $this->assertSame('Admin', $actor->label());
        $this->assertSame($actor->id, Actor::forUser($this->user)->id);
        $this->assertSame($this->user->id, $actor->user->id);
    }

    public function test_user_id_is_a_field_of_user_actors_only(): void
    {
        $this->assertArrayNotHasKey(Actor::FIELD_USER_ID, Actor::getFieldDefinitions());
        $this->assertArrayHasKey(Actor::FIELD_USER_ID, Actor::getFieldDefinitions(Actor::getFieldScopesForType(ActorType::USER)));
        $this->assertSame([Actor::getTypeScope(ActorType::USER)], Field::where('fields->code', Actor::FIELD_USER_ID)->first()->usages()->pluck('scope')->all());
    }

    public function test_participant_takes_part_once_per_role(): void
    {
        $point = $this->makePoint(PointType::TASK);
        $actor = Actor::forUser($this->user);

        foreach ([ParticipantRole::AUTHOR, ParticipantRole::EXECUTOR] as $role) {
            $participant = new Participant();
            $participant->fill([Participant::FIELD_POINT_ID => $point->id, Participant::FIELD_ACTOR_ID => $actor->id, Participant::FIELD_ROLE => $role]);
            $participant->save();
        }

        $this->assertSame(2, $point->participants()->count());
        $this->assertSame($actor->id, $point->participants()->first()->actor->id);

        $this->expectException(UniqueConstraintViolationException::class);

        $duplicate = new Participant();
        $duplicate->fill([Participant::FIELD_POINT_ID => $point->id, Participant::FIELD_ACTOR_ID => $actor->id, Participant::FIELD_ROLE => ParticipantRole::EXECUTOR]);
        $duplicate->save();
    }

    public function test_code_fields_are_seeded(): void
    {
        foreach ([Point::FIELD_TYPE, Point::FIELD_PARENT_ID, Point::FIELD_EXCERPT, Participant::FIELD_ROLE] as $code) {
            $this->assertTrue(Field::where('fields->code', $code)->first()?->isSystem(), $code);
        }
    }

    public function test_points_of_a_participant(): void
    {
        $actor = Actor::forUser($this->user);
        $mine = $this->makePoint(PointType::TASK);
        $authored = $this->makePoint(PointType::TASK);
        $this->makePoint(PointType::TASK);

        foreach ([[$mine, ParticipantRole::EXECUTOR], [$authored, ParticipantRole::AUTHOR]] as [$point, $role]) {
            $participant = new Participant();
            $participant->fill([Participant::FIELD_POINT_ID => $point->id, Participant::FIELD_ACTOR_ID => $actor->id, Participant::FIELD_ROLE => $role]);
            $participant->save();
        }

        $this->assertSame([$mine->id], Point::query()->whereParticipant($actor, ParticipantRole::EXECUTOR)->pluck('id')->all());
        $this->assertSame([$mine->id, $authored->id], Point::query()->whereParticipant($actor)->orderBy('id')->pluck('id')->all());
    }
}
