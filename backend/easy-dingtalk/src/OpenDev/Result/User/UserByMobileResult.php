<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Result\User;

use BeDelightful\EasyDingTalk\Kernel\Exceptions\InvalidResultException;
use BeDelightful\EasyDingTalk\OpenDev\Result\AbstractResult;

class UserByMobileResult extends AbstractResult
{
    private string $userId;

    public function buildByRawData(array $rawData): void
    {
        if (empty($rawData['userid'])) {
            throw new InvalidResultException('userid cannot be empty');
        }

        $this->userId = $rawData['userid'];
    }

    public function getUserid(): string
    {
        return $this->userId;
    }
}
