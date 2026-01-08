<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Enum;

use function Hyperf\Translation\__;

enum DelightfulOperationEnum: string
{
    case QUERY = 'query';
    case EDIT = 'edit';

    /**
     * 标签，使用 i18n 翻译.
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * 对应的 i18n translation key.
     */
    public function translationKey(): string
    {
        return 'permission.operation.' . $this->value;
    }
}
