<?php

namespace Amarenkov\MutableContentTasks\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsImmutable as CFAIsImmutable;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\LovCode as CFALovCode;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\ObjectClass as CFAObjectClass;

use Amarenkov\MutableContentTasks\Lovs\ParticipantRole;

/**
 * An actor taking part in a point with a role.
 */
#[Table('tasks.participants')]
#[ClassLabel('Participant')]
class Participant extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_OBJECT), CFALabel('Point'), CFAIsRequired, CFAIsImmutable, CFAObjectClass(Point::class)]
    public const FIELD_POINT_ID = 'point_id';

    #[CFAType(Type::TYPE_OBJECT), CFALabel('Actor'), CFAIsRequired, CFAObjectClass(Actor::class)]
    public const FIELD_ACTOR_ID = 'actor_id';

    #[CFAType(Type::TYPE_LOV_ITEM), CFALabel('Role'), CFAIsRequired, CFALovCode(ParticipantRole::CLASS_CODE)]
    public const FIELD_ROLE = 'role';

    // static
    protected static array|bool|null $fieldDefinitions = null;

    public static function uniqueFieldSets(): array
    {
        return [[self::FIELD_POINT_ID, self::FIELD_ACTOR_ID, self::FIELD_ROLE]];
    }

    // public
    public function point(): BelongsTo
    {
        return $this->belongsTo(Point::class, self::FIELD_POINT_ID);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class, self::FIELD_ACTOR_ID);
    }
}
