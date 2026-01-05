<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Entity\Item;

use App\Domain\Chat\Entity\AbstractEntity;
use App\Domain\OrganizationEnvironment\Entity\Facade\OpenPlatformConfigInterface;

class OpenPlatformConfigItem extends AbstractEntity implements OpenPlatformConfigInterface
{
    protected string $appId;

    public function initObject(array $data): static
    {
        $this->initProperty($data);
        return $this;
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }
}
