<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Chat\Entity\DelightfulMessageVersionEntity;
use App\Domain\Chat\Service\AbstractDomainService;

class DelightfulMessageVersionDomainService extends AbstractDomainService
{
    /**
     * getmessageversioncolumntable,by version_id ascending.
     * @return null|DelightfulMessageVersionEntity[]
     */
    public function getMessageVersions(string $delightfulMessageId): ?array
    {
        $messageVersions = $this->delightfulChatMessageVersionsRepository->getMessageVersions($delightfulMessageId);
        if (empty($messageVersions)) {
            return null;
        }
        // by version_id ascending
        usort($messageVersions, function ($a, $b) {
            return $a->getVersionId() <=> $b->getVersionId();
        });
        return $messageVersions;
    }
}
