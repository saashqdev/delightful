<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Endpoint\User;

use Delightful\EasyDingTalk\OpenDev\Result\User\AdminResult;
use Delightful\EasyDingTalk\OpenDev\Result\User\UserByCodeResult;
use Delightful\EasyDingTalk\OpenDev\Result\User\UserByMobileResult;
use Delightful\EasyDingTalk\OpenDev\Result\User\UserListResult;
use Delightful\EasyDingTalk\OpenDev\Result\User\UserResult;

class UserFactory
{
    public static function createUserListResultByRawData(array $rawData): UserListResult
    {
        return new UserListResult($rawData);
    }

    public static function createUserResultByRawData(array $rawData): UserResult
    {
        return new UserResult($rawData);
    }

    public static function createUserByCodeResultByRawData(array $rawData): UserByCodeResult
    {
        return new UserByCodeResult($rawData);
    }

    public static function createAdminResultByRawData(array $rawData): AdminResult
    {
        return new AdminResult($rawData);
    }

    public static function createUserResultByMobileRawData(array $rawData): UserByMobileResult
    {
        return new UserByMobileResult($rawData);
    }
}
