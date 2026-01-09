<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation as ContactDataIsolation;
use App\Domain\Contact\Service\DelightfulAccountDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentUserDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;

/**
 * Delightfuluserinfoapplicationservice.
 *
 * 聚合user的基本info、accountinfo和departmentinfo，提供完整的userinfo。
 */
class DelightfulUserInfoAppService extends AbstractAppService
{
    public function __construct(
        protected readonly DelightfulUserDomainService $userDomainService,
        protected readonly DelightfulAccountDomainService $accountDomainService,
        protected readonly DelightfulDepartmentUserDomainService $departmentUserDomainService,
        protected readonly DelightfulDepartmentDomainService $departmentDomainService,
    ) {
    }

    /**
     * get完整的userinfo.
     *
     * @param string $userId userID
     * @param ContactDataIsolation $dataIsolation data隔离object
     * @return array containuser完整info的array
     */
    public function getUserInfo(string $userId, ContactDataIsolation $dataIsolation): array
    {
        // get基本userinfo
        $userEntity = $this->userDomainService->getUserById($userId);
        if (! $userEntity) {
            return $this->getEmptyUserInfo($userId);
        }

        // getaccountinfo
        $accountEntity = null;
        if ($userEntity->getDelightfulId()) {
            $accountEntity = $this->accountDomainService->getAccountInfoByDelightfulId($userEntity->getDelightfulId());
        }

        // getdepartmentuser关联info
        $departmentUserEntities = $this->departmentUserDomainService->getDepartmentUsersByUserIds([$userId], $dataIsolation);

        // 提取工号和职位
        $workNumber = '';
        $position = '';
        if (! empty($departmentUserEntities)) {
            $firstDepartmentUser = $departmentUserEntities[0];
            $workNumber = $firstDepartmentUser->getEmployeeNo() ?? '';
            $position = $firstDepartmentUser->getJobTitle() ?? '';
        }

        // getdepartment详细info
        $departments = $this->getDepartmentsInfo($departmentUserEntities, $dataIsolation);

        return [
            'id' => $userId,
            'nickname' => $userEntity->getNickname() ?? '',
            'real_name' => $accountEntity?->getRealName() ?? '',
            'avatar_url' => $userEntity->getAvatarUrl() ?? '',
            'work_number' => $workNumber,
            'position' => $position,
            'departments' => $departments,
        ];
    }

    /**
     * 批量getuserinfo.
     *
     * @param array $userIds userIDarray
     * @param ContactDataIsolation $dataIsolation data隔离object
     * @return array userinfoarray，键为userID
     */
    public function getBatchUserInfo(array $userIds, ContactDataIsolation $dataIsolation): array
    {
        $result = [];
        foreach ($userIds as $userId) {
            $result[$userId] = $this->getUserInfo($userId, $dataIsolation);
        }
        return $result;
    }

    /**
     * checkuser是否存在.
     *
     * @param string $userId userID
     * @return bool user是否存在
     */
    public function userExists(string $userId): bool
    {
        $userEntity = $this->userDomainService->getUserById($userId);
        return $userEntity !== null;
    }

    /**
     * getuser的主要departmentinfo.
     *
     * @param string $userId userID
     * @param ContactDataIsolation $dataIsolation data隔离object
     * @return null|array 主要departmentinfo，如果没有则returnnull
     */
    public function getUserPrimaryDepartment(string $userId, ContactDataIsolation $dataIsolation): ?array
    {
        $userInfo = $this->getUserInfo($userId, $dataIsolation);
        return $userInfo['departments'][0] ?? null;
    }

    /**
     * getdepartmentinfo.
     *
     * @param array $departmentUserEntities departmentuser关联info
     * @param ContactDataIsolation $dataIsolation data隔离object
     * @return array departmentinfoarray
     */
    private function getDepartmentsInfo(array $departmentUserEntities, ContactDataIsolation $dataIsolation): array
    {
        if (empty($departmentUserEntities)) {
            return [];
        }

        // getdepartmentID
        $departmentIds = array_column($departmentUserEntities, 'department_id');
        $departments = $this->departmentDomainService->getDepartmentByIds($dataIsolation, $departmentIds, true);

        // builddepartmentarray
        $departmentArray = [];
        foreach ($departmentUserEntities as $departmentUserEntity) {
            $departmentEntity = $departments[$departmentUserEntity->getDepartmentId()] ?? null;
            if (! $departmentEntity) {
                continue;
            }

            // getpathdepartment
            $pathNames = [];
            $pathDepartments = explode('/', $departmentEntity->getPath());
            $pathDepartmentEntities = $this->departmentDomainService->getDepartmentByIds($dataIsolation, $pathDepartments, true);

            foreach ($pathDepartments as $pathDepartmentId) {
                if (isset($pathDepartmentEntities[$pathDepartmentId]) && $pathDepartmentEntities[$pathDepartmentId]->getName() !== '') {
                    $pathNames[] = $pathDepartmentEntities[$pathDepartmentId]->getName();
                }
            }

            $departmentArray[] = [
                'id' => $departmentEntity->getDepartmentId(),
                'name' => $departmentEntity->getName(),
                'path' => implode('/', $pathNames),
            ];
        }

        return $departmentArray;
    }

    /**
     * build空的userinfo.
     *
     * @param string $userId userID
     * @return array 空的userinfoarray
     */
    private function getEmptyUserInfo(string $userId): array
    {
        return [
            'id' => $userId,
            'nickname' => '',
            'real_name' => '',
            'work_number' => '',
            'position' => '',
            'departments' => [],
        ];
    }
}
