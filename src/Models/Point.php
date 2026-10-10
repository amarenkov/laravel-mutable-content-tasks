<?php

namespace Amarenkov\MutableContentTasks\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Amarenkov\MutableContent\Domain\Field\Field as DomainField;
use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Field\Common\Description as CommonFieldDescription;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\LovCode as CFALovCode;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\ObjectClass as CFAObjectClass;

use Amarenkov\MutableContentTasks\Lovs\PointType;

/**
 * A point: a task, a comment or any other type; points form a tree, so any point can be split into parts.
 */
#[Table('tasks.points')]
#[ClassLabel('Point')]
#[CommonFieldDescription]
class Point extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_LOV_ITEM), CFALabel('Type'), CFAIsRequired, CFALovCode(PointType::CLASS_CODE)]
    public const FIELD_TYPE = 'type';

    #[CFAType(Type::TYPE_OBJECT), CFALabel('Parent'), CFAObjectClass(Point::class)]
    public const FIELD_PARENT_ID = 'parent_id';

    #[CFAType(Type::TYPE_STRING), CFALabel('Excerpt')]
    public const FIELD_EXCERPT = 'excerpt';

    public const EXCERPT_MAX_LENGTH = 255;

    protected const EXCERPT_CUT_LENGTH = 250;

    // static
    protected static array|bool|null $fieldDefinitions = null;

    public static function typeField(): ?string
    {
        return self::FIELD_TYPE;
    }

    /**
     * Excerpt from a text: the text itself, cut with an ellipsis when longer than the excerpt allows.
     */
    public static function prepareExcerpt(string $text): string
    {
        return mb_strlen($text) > self::EXCERPT_MAX_LENGTH ? mb_substr($text, 0, self::EXCERPT_CUT_LENGTH).'...' : $text;
    }

    protected static function booted(): void
    {
        static::saving(function (Point $point) {
            $excerpt = (string)$point->getField(self::FIELD_EXCERPT);
            $description = (string)$point->getField(DomainField::COMMON_CODE_DESCRIPTION);

            if ($excerpt === '' && $description !== '') {
                $point->{self::FIELD_EXCERPT} = static::prepareExcerpt($description);
            }
        });
    }

    // public
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Point::class, self::FIELD_PARENT_ID);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Point::class, self::FIELD_PARENT_ID);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, Participant::FIELD_POINT_ID);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, TimeEntry::FIELD_POINT_ID);
    }

    /**
     * Excerpt in one line, cut to the length.
     */
    public function oneLineExcerpt(int $length = 20): string
    {
        $result = str_replace("\n", '', (string)$this->getField(self::FIELD_EXCERPT));

        return mb_strlen($result) > $length ? mb_substr($result, 0, $length).'...' : $result;
    }
}
