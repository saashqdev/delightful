<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Entity\ValueObject;

enum DeploymentEnum: string
{
    // 国inside saas
    case SaaS = 'saas';

    // 国际 saas
    case InternationalSaaS = 'international_saas';

    // 开source
    case OpenSource = 'open_source';

    // 未知
    case Unknown = 'unknown';
}
