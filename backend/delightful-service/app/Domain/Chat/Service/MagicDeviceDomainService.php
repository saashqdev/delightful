<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Domain\Chat\Repository\Persistence\DelightfulDeviceRepository;

readonly class DelightfulDeviceDomainService
{
    public function __construct(
        private DelightfulDeviceRepository $deviceRepository,
    ) {
    }

    public function createDeviceId(string $uid, int $osType, string $sid): string
    {
        return (string) $this->deviceRepository->createDeviceId($uid, $osType, $sid);
    }
}
