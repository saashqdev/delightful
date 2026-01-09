<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms\Enum;

enum SmsTypeEnum: string
{
    /*
     * verify码,customizevalid期
     */
    case VERIFICATION_WITH_EXPIRATION = 'verification_with_expiration';

    public static function getDriverType(string $smsType, string $sign, string $phone): SmsDriverTypeEnum
    {
        return SmsDriverTypeEnum::VOLCENGINE;
    }
}
