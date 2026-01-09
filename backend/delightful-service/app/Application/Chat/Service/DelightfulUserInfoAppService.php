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
 * aggregateuserbasicinfo,accountinfoanddepartmentinfo,providecompleteuserinfo.
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
     * getcompleteuserinfo.
     *
     * @param string $userId userID
     * @param ContactDataIsolation $dataIsolation dataisolationobject
     * @return array containusercompleteinfoarray
     */
    public function getUserInfo(string $userId, ContactDataIsolation $dataIsolation): array
    {
        // getbasicuserinfo
        $userEntity = $this->userDomainService->getUserById($userId);
        if (! $userEntity) {
            return $this->getEmptyUserInfo($userId);
        }

        // getaccountinfo
        $accountEntity = null;
        if ($userEntity->getDelightfulId()) {
            $accountEntity = $this->accountDomainService->getAccountInfoByDelightfulId($userEntity->getDelightfulId());
        }

        // getdepartmentuserassociateinfo
        $departmentUserEntities = $this->departmentUserDomainService->getDepartmentUsersByUserIds([$userId], $dataIsolation);

        // extract工numberand职位
        $workNumber = '';
        $position = '';
        if (! empty($departmentUserEntities)) {
            $firstDepartmentUser = $departmentUserEntities[0];
            $workNumber = $firstDepartmentUser->getEmployeeNo() ?? '';
            $position = $firstDepartmentUser->getJobTitle() ?? '';
        }

        // getdepartmentdetailedinfo
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
     * batchquantitygetuserinfo.
     *
     * @param array $userIds userIDarray
     * @param ContactDataIsolation $dataIsolation dataisolationobject
     * @return array userinfoarray,keyforuserID
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
     * checkuserwhether存in.
     *
     * @param string $userId userID
     * @return bool userwhether存in
     */
    public function userExists(string $userId): bool
    {
        $userEntity = $this->userDomainService->getUserById($userId);
        return $userEntity !== null;
    }

    /**
     * getusermaindepartmentinfo.
     *
     * @param string $userId userID
     * @param ContactDataIsolation $dataIsolation dataisolationobject
     * @return null|array maindepartmentinfo,ifnothavethenreturnnull
     */
    public function getUserPrimaryDepartment(string $userId, ContactDataIsolation $dataIsolation): ?array
    {
        $userInfo = $this->getUserInfo($userId, $dataIsolation);
        return $userInfo['departments'][0] ?? null;
    }

    /**
     * getdepartmentinfo.
     *
     * @param array $departmentUserEntities departmentuserassociateinfo
     * @param ContactDataIsolation $dataIsolation dataisolationobject
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
     * buildemptyuserinfo.
     *
     * @param string $userId userID
     * @return array emptyuserinfoarray
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
