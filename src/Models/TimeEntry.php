<?php

namespace Amarenkov\MutableContentTasks\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\ObjectClass as CFAObjectClass;

/**
 * Time an actor worked, on a point or on nothing in particular; an entry without its end is running.
 */
#[Table('tasks.time_entries')]
#[ClassLabel('Time entry')]
class TimeEntry extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_OBJECT), CFALabel('Actor'), CFAIsRequired, CFAObjectClass(Actor::class)]
    public const FIELD_ACTOR_ID = 'actor_id';

    #[CFAType(Type::TYPE_DATETIME), CFALabel('From'), CFAIsRequired]
    public const FIELD_FROM = 'from';

    #[CFAType(Type::TYPE_DATETIME), CFALabel('To')]
    public const FIELD_TO = 'to';

    #[CFAType(Type::TYPE_OBJECT), CFALabel('Point'), CFAObjectClass(Point::class)]
    public const FIELD_POINT_ID = 'point_id';

    #[CFAType(Type::TYPE_STRING), CFALabel('Comment')]
    public const FIELD_COMMENT = 'comment';

    // static
    protected static array|bool|null $fieldDefinitions = null;

    /**
     * Running entry of the actor, if any.
     */
    public static function running(Actor $actor): ?self
    {
        return self::query()
            ->whereField(self::FIELD_ACTOR_ID, $actor->id)
            ->whereField(self::FIELD_TO, null)
            ->orderByDesc(self::FIELD_FROM)
            ->first();
    }

    /**
     * Start an entry of the actor now, stopping the running one.
     */
    public static function start(Actor $actor, ?Point $point = null, ?string $comment = null): self
    {
        return DB::transaction(function () use ($actor, $point, $comment) {
            $now = Carbon::now();

            static::stop($actor, $now);

            $entry = new self();
            $entry->fill([
                self::FIELD_ACTOR_ID => $actor->id,
                self::FIELD_FROM => $now,
                self::FIELD_POINT_ID => $point?->id,
                self::FIELD_COMMENT => $comment,
            ]);
            $entry->save();

            return $entry;
        });
    }

    /**
     * Stop the running entry of the actor; null if nothing is running.
     */
    public static function stop(Actor $actor, ?Carbon $at = null): ?self
    {
        $entry = static::running($actor);

        if (!$entry) {
            return null;
        }

        $entry->{self::FIELD_TO} = $at ?? Carbon::now();
        $entry->save();

        return $entry;
    }

    // public
    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class, self::FIELD_ACTOR_ID);
    }

    public function point(): BelongsTo
    {
        return $this->belongsTo(Point::class, self::FIELD_POINT_ID);
    }

    public function isRunning(): bool
    {
        return $this->getField(self::FIELD_TO) === null;
    }

    /**
     * Length in seconds, up to now for a running entry.
     */
    public function seconds(): int
    {
        $from = $this->getDateTime(self::FIELD_FROM);

        if (!$from) {
            return 0;
        }

        return (int)$from->diffInSeconds($this->getDateTime(self::FIELD_TO) ?? Carbon::now());
    }
}
