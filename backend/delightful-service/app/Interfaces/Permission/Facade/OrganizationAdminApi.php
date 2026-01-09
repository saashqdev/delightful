<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Permission\Facade;

use App\Application\Kernel\Enum\DelightfulAdminResourceEnum;
use App\Application\Kernel\Enum\DelightfulOperationEnum;
use App\Application\Permission\Service\OrganizationAdminAppService;
use App\Infrastructure\Core\Traits\DataIsolationTrait;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\Permission\Annotation\CheckPermission;
use App\Interfaces\Permission\Assembler\OrganizationAdminAssembler;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;
use InvalidArgumentException;

#[ApiResponse('low_code')]
class OrganizationAdminApi extends AbstractPermissionApi
{
    use DataIsolationTrait;

    #[Inject]
    protected OrganizationAdminAppService $organizationAdminAppService;

    /**
     * getorganization管理员列表.
     */
    #[CheckPermission(DelightfulAdminResourceEnum::ORGANIZATION_ADMIN, DelightfulOperationEnum::QUERY)]
    public function list(): array
    {
        $authorization = $this->getAuthorization();
        $dataIsolation = $this->createDataIsolation($authorization);

        $page = intval($this->request->query('page', 1));
        $pageSize = intval($this->request->query('page_size', 10));
        $pageObject = new Page($page, $pageSize);
        $result = $this->organizationAdminAppService->queries($dataIsolation, $pageObject);

        $listDto = OrganizationAdminAssembler::assembleListWithUserInfo($result['list']);
        $listDto->setTotal($result['total']);
        $listDto->setPage($page);
        $listDto->setPageSize($pageSize);
        return $listDto->toArray();
    }

    /**
     * getorganization管理员详情.
     */
    #[CheckPermission(DelightfulAdminResourceEnum::ORGANIZATION_ADMIN, DelightfulOperationEnum::QUERY)]
    public function show(int $id): array
    {
        $authorization = $this->getAuthorization();
        $dataIsolation = $this->createDataIsolation($authorization);

        $organizationAdminData = $this->organizationAdminAppService->show($dataIsolation, $id);

        return OrganizationAdminAssembler::assembleWithUserInfo($organizationAdminData)->toArray();
    }

    /**
     * 授予userorganization管理员permission.
     */
    #[CheckPermission(DelightfulAdminResourceEnum::ORGANIZATION_ADMIN, DelightfulOperationEnum::EDIT)]
    public function grant(): array
    {
        $authorization = $this->getAuthorization();
        $dataIsolation = $this->createDataIsolation($authorization);
        $grantorUserId = $authorization->getId();

        $userId = $this->request->input('user_id');
        $remarks = $this->request->input('remarks');

        $organizationAdmin = $this->organizationAdminAppService->grant($dataIsolation, $userId, $grantorUserId, $remarks);

        // get包含userinformation的完整data
        $organizationAdminData = $this->organizationAdminAppService->show($dataIsolation, $organizationAdmin->getId());

        return OrganizationAdminAssembler::assembleWithUserInfo($organizationAdminData)->toArray();
    }

    /**
     * deleteorganization管理员.
     */
    #[CheckPermission(DelightfulAdminResourceEnum::ORGANIZATION_ADMIN, DelightfulOperationEnum::EDIT)]
    public function destroy(int $id): array
    {
        $authorization = $this->getAuthorization();
        $dataIsolation = $this->createDataIsolation($authorization);

        $this->organizationAdminAppService->destroy($dataIsolation, $id);
        return [];
    }

    /**
     * 转让organizationcreate人身份.
     */
    #[CheckPermission(DelightfulAdminResourceEnum::ORGANIZATION_ADMIN, DelightfulOperationEnum::EDIT)]
    public function transferOwner(): array
    {
        $authorization = $this->getAuthorization();
        $dataIsolation = $this->createDataIsolation($authorization);
        $currentOwnerUserId = $authorization->getId();

        $newOwnerUserId = $this->request->input('user_id');
        if (empty($newOwnerUserId)) {
            throw new InvalidArgumentException('新的organizationcreate人userID不能为空');
        }

        $this->organizationAdminAppService->transferOwnership(
            $dataIsolation,
            $newOwnerUserId,
            $currentOwnerUserId
        );

        return [];
    }
}
