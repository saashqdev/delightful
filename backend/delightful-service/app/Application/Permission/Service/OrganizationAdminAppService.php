<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Permission\Service;

use App\Application\Kernel\AbstractKernelAppService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentUserDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\Permission\Entity\OrganizationAdminEntity;
use App\Domain\Permission\Service\OrganizationAdminDomainService;
use App\Infrastructure\Core\ValueObject\Page;
use Exception;

class OrganizationAdminAppService extends AbstractKernelAppService
{
    public function __construct(
        private readonly OrganizationAdminDomainService $organizationAdminDomainService,
        private readonly DelightfulUserDomainService $userDomainService,
        private readonly DelightfulDepartmentUserDomainService $departmentUserDomainService,
        private readonly DelightfulDepartmentDomainService $departmentDomainService
    ) {
    }

    /**
     * queryorganization管理员list.
     * @return array{total: int, list: array}
     */
    public function queries(DataIsolation $dataIsolation, Page $page, ?array $filters = null): array
    {
        $result = $this->organizationAdminDomainService->queries($dataIsolation, $page, $filters);

        // getuserinfo
        $organizationAdmins = $result['list'];
        $enrichedList = [];

        foreach ($organizationAdmins as $organizationAdmin) {
            $enrichedData = $this->enrichOrganizationAdminWithUserInfo($dataIsolation, $organizationAdmin);
            $enrichedList[] = $enrichedData;
        }

        return [
            'total' => $result['total'],
            'list' => $enrichedList,
        ];
    }

    /**
     * getorganization管理员详情.
     */
    public function show(DataIsolation $dataIsolation, int $id): array
    {
        $organizationAdmin = $this->organizationAdminDomainService->show($dataIsolation, $id);
        return $this->enrichOrganizationAdminWithUserInfo($dataIsolation, $organizationAdmin);
    }

    /**
     * 根据userIDgetorganization管理员.
     */
    public function getByUserId(DataIsolation $dataIsolation, string $userId): ?OrganizationAdminEntity
    {
        return $this->organizationAdminDomainService->getByUserId($dataIsolation, $userId);
    }

    /**
     * 授予userorganization管理员permission.
     */
    public function grant(DataIsolation $dataIsolation, string $userId, string $grantorUserId, ?string $remarks = null): OrganizationAdminEntity
    {
        return $this->organizationAdminDomainService->grant($dataIsolation, $userId, $grantorUserId, $remarks);
    }

    /**
     * deleteorganization管理员.
     */
    public function destroy(DataIsolation $dataIsolation, int $id): void
    {
        $organizationAdmin = $this->organizationAdminDomainService->show($dataIsolation, $id);
        $this->organizationAdminDomainService->destroy($dataIsolation, $organizationAdmin);
    }

    /**
     * 转让organizationcreate人身份.
     */
    public function transferOwnership(DataIsolation $dataIsolation, string $newOwnerUserId, string $currentOwnerUserId): void
    {
        $this->organizationAdminDomainService->transferOrganizationCreator(
            $dataIsolation,
            $currentOwnerUserId,
            $newOwnerUserId,
            $currentOwnerUserId // 操作者就是当前create者
        );
    }

    /**
     * 丰富organization管理员实体的userinfo.
     */
    private function enrichOrganizationAdminWithUserInfo(DataIsolation $dataIsolation, OrganizationAdminEntity $organizationAdmin): array
    {
        // getuser基本info
        $userInfo = $this->getUserInfo($organizationAdmin->getUserId());

        // getauthorization人info
        $grantorInfo = [];
        if ($organizationAdmin->getGrantorUserId()) {
            $grantorInfo = $this->getUserInfo($organizationAdmin->getGrantorUserId());
        }

        // getdepartmentinfo
        $departmentInfo = $this->getDepartmentInfo($dataIsolation, $organizationAdmin->getUserId());

        return [
            'organization_admin' => $organizationAdmin,
            'user_info' => $userInfo,
            'grantor_info' => $grantorInfo,
            'department_info' => $departmentInfo,
        ];
    }

    /**
     * getuserinfo.
     */
    private function getUserInfo(string $userId): array
    {
        $user = $this->userDomainService->getUserById($userId);
        if (! $user) {
            return [];
        }

        return [
            'user_id' => $user->getUserId(),
            'nickname' => $user->getNickname(),
            'avatar_url' => $user->getAvatarUrl(),
        ];
    }

    /**
     * getuserdepartmentinfo.
     */
    private function getDepartmentInfo(DataIsolation $dataIsolation, string $userId): array
    {
        try {
            $departmentUsers = $this->departmentUserDomainService->getDepartmentUsersByUserIds(
                [$userId],
                $dataIsolation
            );

            if (empty($departmentUsers)) {
                return [];
            }

            $departmentUser = $departmentUsers[0];

            // getdepartment详细info
            $department = $this->departmentDomainService->getDepartmentById(
                $dataIsolation,
                $departmentUser->getDepartmentId()
            );

            return [
                'name' => $department ? $department->getName() : '',
                'job_title' => $departmentUser->getJobTitle(),
            ];
        } catch (Exception $e) {
            // 如果getdepartmentinfofail，return空array
            return [];
        }
    }
}
