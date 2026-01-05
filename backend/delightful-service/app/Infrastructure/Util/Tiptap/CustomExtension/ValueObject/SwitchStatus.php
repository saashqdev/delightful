<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Tiptap\CustomExtension\ValueObject;

enum SwitchStatus: string
{
    case ON = 'on';
    case OFF = 'off';
}
