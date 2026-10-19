<?php

namespace Amarenkov\MutableContentTasks\Lovs;

use Amarenkov\MutableContent\Domain\Lov\Lov;

use Amarenkov\MutableContent\Attributes\Class\Code as ClassCode;
use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Lov\Item as LovItem;

#[ClassCode(PointStatus::CLASS_CODE)]
#[ClassLabel('Point status')]
#[LovItem(PointStatus::OPEN, 'Opened')]
#[LovItem(PointStatus::CLOSED, 'Closed')]
class PointStatus extends Lov
{
    // const
    public const CLASS_CODE = 'point_status';

    public const OPEN = 'open';
    public const CLOSED = 'closed';
}
