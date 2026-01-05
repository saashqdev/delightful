<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Domain\Chat\Repository\Persistence\MagicDeviceRepository;

readonly class MagicDeviceDomainService
{
    public function __construct(
        private MagicDeviceRepository $deviceRepository,
    ) {
    }

    public function createDeviceId(string $uid, int $osType, string $sid): string
    {
        return (string) $this->deviceRepository->createDeviceId($uid, $osType, $sid);
    }
}
