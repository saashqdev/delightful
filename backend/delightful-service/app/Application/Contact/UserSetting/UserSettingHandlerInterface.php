<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Contact\UserSetting;

use App\Domain\Contact\Entity\MagicUserSettingEntity;
use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;

interface UserSettingHandlerInterface
{
    public function populateValue(BaseDataIsolation $dataIsolation, MagicUserSettingEntity $setting): void;

    public function generateDefault(): ?MagicUserSettingEntity;
}
