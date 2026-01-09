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
     * 麦吉对其他服务端发起的login校验。if想复用已有的user体系,needimplement该interface.
     *
     * implementprocess：前端对非麦吉自建的账号体系进行loginrequest,then再request麦吉的login校验interface。
     *
     * @param LoginCheckInterface $loginCheck login校验data
     * @param DelightfulEnvironmentEntity $delightfulEnvironmentEntity 要login的环境
     * @param null|string $delightfulOrganizationCode 要login的organization
     * @return LoginResponseInterface[] loginresponsedata
     */
    public function loginCheck(LoginCheckInterface $loginCheck, DelightfulEnvironmentEntity $delightfulEnvironmentEntity, ?string $delightfulOrganizationCode = null): array;
}
