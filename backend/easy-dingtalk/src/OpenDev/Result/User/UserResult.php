<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Result\User;

use Dtyq\EasyDingTalk\Kernel\Exceptions\InvalidResultException;
use Dtyq\EasyDingTalk\OpenDev\Result\AbstractResult;

class UserResult extends AbstractResult
{
    private string $userId;

    private string $unionid;

    /**
     * When the address book is encrypted, this value cannot be obtained, default is empty string.
     */
    private string $name;

    private string $avatar;

    /**
     * When the address book is encrypted, this value cannot be obtained, default is empty string.
     */
    private string $title;

    private string $jobNumber;

    private string $mobile;

    private array $deptIdList;

    private int $sysLevel;

    public function getSysLevel(): int
    {
        return $this->sysLevel;
    }

    public function setSysLevel(int $sysLevel): void
    {
        $this->sysLevel = $sysLevel;
    }

    public function buildByRawData(array $rawData): void
    {
        if (empty($rawData['userid'])) {
            throw new InvalidResultException('userid cannot be empty');
        }
        if (empty($rawData['unionid'])) {
            throw new InvalidResultException('unionid cannot be empty');
        }

        $this->userId = $rawData['userid'];
        $this->unionid = $rawData['unionid'];
        $this->name = $rawData['name'] ?? '';
        $this->avatar = $rawData['avatar'] ?? '';
        $this->title = $rawData['title'] ?? '';
        $this->jobNumber = $rawData['job_number'] ?? '';
        $this->mobile = $rawData['mobile'] ?? '';
        $this->deptIdList = $rawData['dept_id_list'] ?? [];
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getUnionid(): string
    {
        return $this->unionid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMobile(): string
    {
        return $this->mobile;
    }

    public function getDeptIdList(): array
    {
        return $this->deptIdList;
    }

    public function getJobNumber(): string
    {
        return $this->jobNumber;
    }
}
