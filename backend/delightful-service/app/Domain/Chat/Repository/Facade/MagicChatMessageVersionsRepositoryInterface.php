<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Facade;

use App\Domain\Chat\Entity\DelightfulMessageVersionEntity;

interface DelightfulChatMessageVersionsRepositoryInterface
{
    public function createMessageVersion(DelightfulMessageVersionEntity $messageVersionDTO): DelightfulMessageVersionEntity;

    /**
     * @return DelightfulMessageVersionEntity[]
     */
    public function getMessageVersions(string $magicMessageId): array;
}
