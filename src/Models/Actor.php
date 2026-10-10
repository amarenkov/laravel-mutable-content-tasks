<?php

namespace Amarenkov\MutableContentTasks\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Amarenkov\MutableContent\Domain\Field\Field as DomainField;
use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Field\Common\Label as CommonFieldLabel;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsImmutable as CFAIsImmutable;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\LovCode as CFALovCode;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\ForType as CFAForType;

use Amarenkov\MutableContentTasks\Lovs\ActorType;

/**
 * Who acts on points: an application user for now; groups, automations and people without an account later.
 */
#[Table('tasks.actors')]
#[ClassLabel('Actor')]
#[CommonFieldLabel]
class Actor extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_LOV_ITEM), CFALabel('Type'), CFAIsRequired, CFALovCode(ActorType::CLASS_CODE)]
    public const FIELD_TYPE = 'type';

    #[CFAType(Type::TYPE_INT), CFALabel('User'), CFAIsImmutable, CFAForType(ActorType::USER)]
    public const FIELD_USER_ID = 'user_id';

    // static
    protected static array|bool|null $fieldDefinitions = null;

    public static function typeField(): ?string
    {
        return self::FIELD_TYPE;
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public static function userModel(): string
    {
        return config('mutable-content-tasks.user_model') ?? config('auth.providers.users.model');
    }

    /**
     * Actor of the user, created on first use.
     */
    public static function forUser(Authenticatable $user): self
    {
        $actor = self::query()
            ->where(self::FIELD_TYPE, ActorType::USER)
            ->where(self::FIELD_USER_ID, $user->getAuthIdentifier())
            ->first();

        if ($actor) {
            return $actor;
        }

        $actor = new self();
        $actor->fill([
            self::FIELD_TYPE => ActorType::USER,
            self::FIELD_USER_ID => $user->getAuthIdentifier(),
            DomainField::COMMON_CODE_LABEL => $user->name ?? (string)$user->getAuthIdentifier(),
        ]);
        $actor->save();

        return $actor;
    }

    // public
    public function user(): BelongsTo
    {
        return $this->belongsTo(static::userModel(), self::FIELD_USER_ID);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class, TimeEntry::FIELD_ACTOR_ID);
    }

    public function participations(): HasMany
    {
        return $this->hasMany(Participant::class, Participant::FIELD_ACTOR_ID);
    }
}
