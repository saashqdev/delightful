<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\OfficeOragization;

class OfficeOrganizationUtil
{
    public static function getOfficeOrganizationCode(): string
    {
        return config('config.office_organization');
    }
}
