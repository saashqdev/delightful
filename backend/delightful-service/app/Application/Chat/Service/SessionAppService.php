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
        protected DelightfulDepartmentDomainService $delightfulDepartmentDomainService,
        protected DelightfulUserDomainService $delightfulUserDomainService
    ) {
    }

    /**
     * 登录校验.
     * @return LoginResponseDTO[]
     */
    public function LoginCheck(LoginCheckInterface $loginCheck, DelightfulEnvironmentEntity $delightfulEnvironmentEntity, ?string $delightfulOrganizationCode = null): array
    {
        $loginResponses = $this->delightfulUserDomainService->delightfulUserLoginCheck($loginCheck->getAuthorization(), $delightfulEnvironmentEntity, $delightfulOrganizationCode);
        // 增加organizationname和头像
        if (! empty($loginResponses)) {
            // 收集所有organization代码
            $orgCodes = [];
            foreach ($loginResponses as $loginResponse) {
                $orgCode = $loginResponse->getDelightfulOrganizationCode();
                if (! empty($orgCode)) {
                    $orgCodes[] = $orgCode;
                }
            }

            // 如果有organization代码，批量get所有organization的根departmentinformation
            if (! empty($orgCodes)) {
                // 一次性批量get所有organization的根departmentinformation
                $rootDepartments = $this->delightfulDepartmentDomainService->getOrganizationsRootDepartment($orgCodes);

                // 填充登录响应information
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
