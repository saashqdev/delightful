<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Mode\Facade;

use App\Application\Mode\Service\AdminModeGroupAppService;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\AbstractApi;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Auth\PermissionChecker;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Mode\DTO\Request\CreateModeGroupRequest;
use App\Interfaces\Mode\DTO\Request\UpdateModeGroupRequest;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\HttpServer\Contract\RequestInterface;

#[ApiResponse('low_code')]
class AdminModeGroupApi extends AbstractApi
{
    public function __construct(
        RequestInterface $request,
        private AdminModeGroupAppService $modeGroupAppService
    ) {
        parent::__construct($request);
    }

    /**
     * 根据modeIDget分组列table.
     */
    public function getGroupsByModeId(RequestInterface $request, string $modeId): array
    {
        $authorization = $this->getAuthorization();
        $this->checkAuth($authorization);
        return $this->modeGroupAppService->getGroupsByModeId($authorization, $modeId);
    }

    /**
     * get分组详情.
     */
    public function getGroupDetail(RequestInterface $request, string $groupId): array
    {
        $authorization = $this->getAuthorization();
        $this->checkAuth($authorization);
        $result = $this->modeGroupAppService->getGroupById($authorization, $groupId);

        if (! $result) {
            return [];
        }

        return $result;
    }

    /**
     * create分组.
     */
    public function createGroup(CreateModeGroupRequest $request)
    {
        $authorization = $this->getAuthorization();
        $this->checkAuth($authorization);
        $request->validated();
        return $this->modeGroupAppService->createGroup($authorization, $request);
    }

    /**
     * update分组.
     */
    public function updateGroup(UpdateModeGroupRequest $request, string $groupId)
    {
        $authorization = $this->getAuthorization();
        $this->checkAuth($authorization);
        $request->validated();
        return $this->modeGroupAppService->updateGroup($authorization, $groupId, $request);
    }

    /**
     * delete分组.
     */
    public function deleteGroup(RequestInterface $request, string $groupId): array
    {
        $authorization = $this->getAuthorization();
        $this->checkAuth($authorization);
        $this->modeGroupAppService->deleteGroup($authorization, $groupId);
        return ['success' => true];
    }

    private function isCurrentOrganizationOfficial(): bool
    {
        $officialOrganization = config('service_provider.office_organization');
        $organizationCode = $this->getAuthorization()->getOrganizationCode();
        return $officialOrganization === $organizationCode;
    }

    private function checkAuth(DelightfulUserAuthorization $authenticatable)
    {
        $isCurrentOrganizationOfficial = $this->isCurrentOrganizationOfficial();
        $isOrganizationAdmin = PermissionChecker::isOrganizationAdmin($authenticatable->getOrganizationCode(), $authenticatable->getMobile());
        if (! $isCurrentOrganizationOfficial || ! $isOrganizationAdmin) {
            ExceptionBuilder::throw(UserErrorCode::ORGANIZATION_NOT_AUTHORIZE);
        }
    }
}
