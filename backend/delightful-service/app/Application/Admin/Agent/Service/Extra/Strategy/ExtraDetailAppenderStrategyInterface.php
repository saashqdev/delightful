<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Admin\Agent\Service\Extra\Strategy;

use App\Interfaces\Admin\DTO\Extra\SettingExtraDTOInterface;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;

interface ExtraDetailAppenderStrategyInterface
{
    public function appendExtraDetail(SettingExtraDTOInterface $extraDTO, DelightfulUserAuthorization $userAuthorization): SettingExtraDTOInterface;
}
