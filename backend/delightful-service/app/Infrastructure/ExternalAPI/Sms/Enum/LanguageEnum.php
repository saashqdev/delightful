<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms\Enum;

enum LanguageEnum: string
{
    /**
     * simplemiddle.
     */
    case ZH_CN = 'zh_CN';

    /**
     * 美typeEnglish.
     */
    case EN_US = 'en_US';

    /**
     * Indonesian.
     */
    case ID_ID = 'id_ID';
}
