<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Authentication\DTO\LoginResponseDTO;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;
use App\Infrastructure\Core\Contract\Session\LoginCheckInterface;
use App\Infrastructure\Core\Contract\Session\SessionInterface;

class SessionAppService implements SessionInterface
{
    public function __construct(
        protected DelightfulDepartmentDomainService $magicDepartmentDomainService,
        protected DelightfulUserDomainService $magicUserDomainService
    ) {
    }

    /**
     * 登录校验.
     * @return LoginResponseDTO[]
     */
    public function LoginCheck(LoginCheckInterface $loginCheck, DelightfulEnvironmentEntity $magicEnvironmentEntity, ?string $magicOrganizationCode = null): array
    {
        $loginResponses = $this->magicUserDomainService->magicUserLoginCheck($loginCheck->getAuthorization(), $magicEnvironmentEntity, $magicOrganizationCode);
        // 增加组织name和头像
        if (! empty($loginResponses)) {
            // 收集所有组织代码
            $orgCodes = [];
            foreach ($loginResponses as $loginResponse) {
                $orgCode = $loginResponse->getDelightfulOrganizationCode();
                if (! empty($orgCode)) {
                    $orgCodes[] = $orgCode;
                }
            }

            // 如果有组织代码，批量获取所有组织的根部门信息
            if (! empty($orgCodes)) {
                // 一次性批量获取所有组织的根部门信息
                $rootDepartments = $this->magicDepartmentDomainService->getOrganizationsRootDepartment($orgCodes);

                // 填充登录响应信息
                foreach ($loginResponses as $loginResponse) {
                    $orgCode = $loginResponse->getDelightfulOrganizationCode();
                    if (! empty($orgCode) && isset($rootDepartments[$orgCode])) {
                        $loginResponse->setOrganizationName($rootDepartments[$orgCode]->getName() ?? '');
                    }
                }
            }
        }

        return $loginResponses;
    }
}
