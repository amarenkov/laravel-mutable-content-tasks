<?php

namespace Amarenkov\MutableContentTasks\Lovs;

use Amarenkov\MutableContent\Domain\Lov\Lov;

use Amarenkov\MutableContent\Attributes\Class\Code as ClassCode;
use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Lov\Item as LovItem;

#[ClassCode(ActorType::CLASS_CODE)]
#[ClassLabel('Actor type')]
#[LovItem(ActorType::USER, 'User')]
class ActorType extends Lov
{
    // const
    public const CLASS_CODE = 'actor_type';

    public const USER = 'user';
}
