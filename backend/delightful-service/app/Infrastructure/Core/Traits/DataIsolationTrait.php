<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Traits;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Qbhy\HyperfAuth\Authenticatable;

trait DataIsolationTrait
{
    /**
     * @param DelightfulUserAuthorization $authorization
     */
    protected function createDataIsolation(Authenticatable $authorization): DataIsolation
    {
        $dataIsolation = new DataIsolation();
        /* @phpstan-ignore-next-line */
        if ($authorization instanceof DelightfulUserAuthorization) {
            $userId = $authorization->getId();
            $dataIsolation->setCurrentUserId(currentUserId: $userId);
            $dataIsolation->setCurrentDelightfulId(currentDelightfulId: $authorization->getDelightfulId());
            $dataIsolation->setUserType(userType: $authorization->getUserType());
        }
        $dataIsolation->setCurrentOrganizationCode(currentOrganizationCode: $authorization->getOrganizationCode());
        return $dataIsolation;
    }
}
