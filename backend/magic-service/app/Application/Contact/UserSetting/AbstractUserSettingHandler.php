<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Application\Contact\UserSetting;

use App\Domain\Contact\Entity\MagicUserSettingEntity;
use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;

abstract class AbstractUserSettingHandler implements UserSettingHandlerInterface
{
    public function valueGetHandle(BaseDataIsolation $dataIsolation, MagicUserSettingEntity $setting): void
    {
    }

    public function generateDefault(): ?MagicUserSettingEntity
    {
        return null;
    }
}
