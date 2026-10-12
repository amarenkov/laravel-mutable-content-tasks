<?php

namespace Amarenkov\MutableContentTasks\Lovs;

use Amarenkov\MutableContent\Domain\Lov\Lov;

use Amarenkov\MutableContent\Attributes\Class\Code as ClassCode;
use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Lov\Item as LovItem;

#[ClassCode(ParticipantRole::CLASS_CODE)]
#[ClassLabel('Participant role')]
#[LovItem(ParticipantRole::AUTHOR, 'Author')]
#[LovItem(ParticipantRole::EXECUTOR, 'Executor')]
class ParticipantRole extends Lov
{
    // const
    public const CLASS_CODE = 'participant_role';

    public const AUTHOR = 'author';
    public const EXECUTOR = 'executor';
}
