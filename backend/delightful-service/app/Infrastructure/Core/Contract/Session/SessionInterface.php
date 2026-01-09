<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Session;

use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;

interface SessionInterface
{
    /**
     * 麦吉对其他服务端发起的登录校验。if想复用已有的user体系,needimplement该interface.
     *
     * implementprocess：前端对非麦吉自建的账号体系进行登录request,then再request麦吉的登录校验interface。
     *
     * @param LoginCheckInterface $loginCheck 登录校验data
     * @param DelightfulEnvironmentEntity $delightfulEnvironmentEntity 要登录的环境
     * @param null|string $delightfulOrganizationCode 要登录的organization
     * @return LoginResponseInterface[] 登录responsedata
     */
    public function loginCheck(LoginCheckInterface $loginCheck, DelightfulEnvironmentEntity $delightfulEnvironmentEntity, ?string $delightfulOrganizationCode = null): array;
}
