<?php

namespace Amarenkov\MutableContentTasks\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

use Carbon\CarbonInterface;

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
     * Latest entry of the actor, running or not; with onPoint only entries on a point count.
     */
    public static function last(Actor $actor, bool $onPoint = false): ?self
    {
        return self::query()
            ->whereField(self::FIELD_ACTOR_ID, $actor->id)
            ->when($onPoint, fn ($query) => $query->whereField(self::FIELD_POINT_ID, '<>', null))
            ->orderByDesc(self::FIELD_FROM)
            ->first();
    }

    /**
     * Seconds the actor worked on the point, a running entry up to now.
     */
    public static function secondsOn(Actor $actor, Point $point): int
    {
        return self::query()
            ->whereField(self::FIELD_ACTOR_ID, $actor->id)
            ->whereField(self::FIELD_POINT_ID, $point->id)
            ->get()
            ->sum(fn (self $entry) => $entry->seconds());
    }

    /**
     * Seconds the actor worked within the period by point id, most first; entries are cut by the period bounds, a
     * running entry counts up to now, work on nothing in particular goes under an empty key.
     *
     * @return array<int|string, int>
     */
    public static function report(Actor $actor, CarbonInterface $from, CarbonInterface $to): array
    {
        $result = [];

        $entries = self::query()
            ->whereField(self::FIELD_ACTOR_ID, $actor->id)
            ->whereField(self::FIELD_FROM, '<', $to)
            ->where(fn ($query) => $query->whereField(self::FIELD_TO, null)->orWhereField(self::FIELD_TO, '>', $from))
            ->get();

        foreach ($entries as $entry) {
            $key = $entry->pointId() ?? '';
            $result[$key] = ($result[$key] ?? 0) + $entry->secondsWithin($from, $to);
        }

        $result = array_filter($result);
        arsort($result);

        return $result;
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

    public function pointId(): ?int
    {
        $pointId = $this->getField(self::FIELD_POINT_ID);

        return $pointId === null ? null : (int)$pointId;
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

    /**
     * Length in seconds of the part within the period, up to now for a running entry.
     */
    public function secondsWithin(CarbonInterface $from, CarbonInterface $to): int
    {
        $start = $this->getDateTime(self::FIELD_FROM);

        if (!$start) {
            return 0;
        }

        $start = $start->max($from);
        $end = ($this->getDateTime(self::FIELD_TO) ?? Carbon::now())->min($to);

        return $end > $start ? (int)$start->diffInSeconds($end) : 0;
    }
}
