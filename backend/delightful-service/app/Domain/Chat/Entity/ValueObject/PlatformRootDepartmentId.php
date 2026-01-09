<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

use App\Domain\Contact\Entity\ValueObject\PlatformType;

/**
 * 平台rootdepartmentID.
 */
class PlatformRootDepartmentId
{
    public const string Delightful = '-1';

    public const string DingTalk = '1';

    public const string TeamShare = '0';

    public static function getRootDepartmentIdByPlatformType(PlatformType $thirdPlatformType): string
    {
        return match ($thirdPlatformType) {
            PlatformType::Delightful => self::Delightful,
            PlatformType::DingTalk => self::DingTalk,
            PlatformType::Teamshare => self::TeamShare,
            PlatformType::FeiShu => self::Delightful,
            PlatformType::WeCom => self::Delightful,
        };
    }
}
