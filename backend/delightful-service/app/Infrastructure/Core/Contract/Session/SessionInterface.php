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
     * 麦吉对其他服务端发起的登录校验。如果想复用已有的用户体系,需要实现该接口.
     *
     * 实现流程：前端对非麦吉自建的账号体系进行登录请求,然后再请求麦吉的登录校验接口。
     *
     * @param LoginCheckInterface $loginCheck 登录校验数据
     * @param DelightfulEnvironmentEntity $delightfulEnvironmentEntity 要登录的环境
     * @param null|string $delightfulOrganizationCode 要登录的组织
     * @return LoginResponseInterface[] 登录响应数据
     */
    public function loginCheck(LoginCheckInterface $loginCheck, DelightfulEnvironmentEntity $delightfulEnvironmentEntity, ?string $delightfulOrganizationCode = null): array;
}
