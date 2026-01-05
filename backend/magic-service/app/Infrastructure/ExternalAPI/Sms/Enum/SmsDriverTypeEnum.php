<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms\Enum;

enum SmsDriverTypeEnum: string
{
    // 火山短信
    case VOLCENGINE = 'volcengine';
}
