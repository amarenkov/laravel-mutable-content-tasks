<?php

namespace Amarenkov\MutableContentTasks\Services\Concerns;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContentTasks\Models\Actor;

/**
 * Who does the action and why: the actor's user becomes the change log author.
 */
trait ActsAs
{
    // protected
    protected ?Actor $actor = null;

    protected ?string $logComment = null;

    protected function prepareLog(ModelWithFields $model): void
    {
        $user = $this->actor?->getField(Actor::FIELD_USER_ID);

        $model->withLogContext($this->logComment)->user($user === null ? null : (int)$user);
    }

    // public
    public function by(?Actor $actor): static
    {
        $this->actor = $actor;

        return $this;
    }

    public function logComment(?string $comment): static
    {
        $this->logComment = $comment;

        return $this;
    }
}
