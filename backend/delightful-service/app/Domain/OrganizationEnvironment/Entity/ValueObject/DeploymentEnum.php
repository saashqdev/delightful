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

    // international saas
    case InternationalSaaS = 'international_saas';

    // opensource
    case OpenSource = 'open_source';

    // unknown
    case Unknown = 'unknown';
}
