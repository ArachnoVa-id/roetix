<?php

namespace App\Enums;

use App\Enums\Traits\BaseEnumTrait;

enum EnumVersionType: string
{
    use BaseEnumTrait;

    case ARRAY = 'array';
    case DEFAULT = 'default';
}
