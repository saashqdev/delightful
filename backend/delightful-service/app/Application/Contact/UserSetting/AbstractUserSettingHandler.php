<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Contact\UserSetting;

use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;

abstract class AbstractUserSettingHandler implements UserSettingHandlerInterface
{
    public function valueGetHandle(BaseDataIsolation $dataIsolation, DelightfulUserSettingEntity $setting): void
    {
    }

    public function generateDefault(): ?DelightfulUserSettingEntity
    {
        return null;
    }
}
