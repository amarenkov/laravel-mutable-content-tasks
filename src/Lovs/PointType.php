<?php

namespace Amarenkov\MutableContentTasks\Lovs;

use Amarenkov\MutableContent\Domain\Lov\Lov;

use Amarenkov\MutableContent\Attributes\Class\Code as ClassCode;
use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Lov\Item as LovItem;

#[ClassCode(PointType::CLASS_CODE)]
#[ClassLabel('Point type')]
#[LovItem(PointType::TASK, 'Task')]
#[LovItem(PointType::COMMENT, 'Comment')]
class PointType extends Lov
{
    // const
    public const CLASS_CODE = 'point_type';

    public const TASK = 'task';
    public const COMMENT = 'comment';
}
