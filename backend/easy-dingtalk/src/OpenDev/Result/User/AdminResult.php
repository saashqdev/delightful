<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Result\User;

use Delightful\EasyDingTalk\Kernel\Exceptions\InvalidResultException;
use Delightful\EasyDingTalk\OpenDev\Result\AbstractResult;

class AdminResult extends AbstractResult
{
    private string $userId;

    private int $sysLevel;

    public function buildByRawData(array $rawData): void
    {
        if (empty($rawData['userid'])) {
            throw new InvalidResultException('userid cannot be empty');
        }
        if (empty($rawData['sys_level'])) {
            throw new InvalidResultException('sys_level cannot be empty');
        }
        $this->userId = $rawData['userid'];
        $this->sysLevel = $rawData['sys_level'];
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getSysLevel(): int
    {
        return $this->sysLevel;
    }
}
