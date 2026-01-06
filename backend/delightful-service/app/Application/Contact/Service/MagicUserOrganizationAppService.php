<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Contact\Service;

use App\Application\Contact\DTO\DelightfulUserOrganizationItemDTO;
use App\Application\Contact\DTO\DelightfulUserOrganizationListDTO;
use App\Application\Contact\Support\OrganizationProductResolver;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\OrganizationEnvironment\Service\DelightfulOrganizationEnvDomainService;
use App\Domain\OrganizationEnvironment\Service\OrganizationDomainService;
use App\Domain\Permission\Service\OrganizationAdminDomainService;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Hyperf\Di\Annotation\Inject;
use Throwable;

/**
 * 用户当前组织应用服务
 */
class DelightfulUserOrganizationAppService
{
    #[Inject]
    protected DelightfulUserDomainService $userDomainService;

    #[Inject]
    protected DelightfulUserSettingAppService $userSettingAppService;

    #[Inject]
    protected DelightfulOrganizationEnvDomainService $organizationEnvDomainService;

    #[Inject]
    protected OrganizationDomainService $organizationDomainService;

    #[Inject]
    protected OrganizationAdminDomainService $organizationAdminDomainService;

    #[Inject]
    protected OrganizationProductResolver $organizationProductResolver;

    /**
     * 获取用户当前组织代码
     */
    public function getCurrentOrganizationCode(string $magicId): ?array
    {
        return $this->userSettingAppService->getCurrentOrganizationDataByDelightfulId($magicId);
    }

    /**
     * 设置用户当前组织代码
     */
    public function setCurrentOrganizationCode(string $magicId, string $magicOrganizationCode): array
    {
        // 1. 查询用户是否在指定组织中
        $userOrganizations = $this->userDomainService->getUserOrganizationsByDelightfulId($magicId);
        if (! in_array($magicOrganizationCode, $userOrganizations, true)) {
            ExceptionBuilder::throw(UserErrorCode::ORGANIZATION_NOT_EXIST);
        }

        // 2. 查询这个组织的相关信息：magic_organizations_environment
        $organizationEnvEntity = $this->organizationEnvDomainService->getOrganizationEnvironmentByDelightfulOrganizationCode($magicOrganizationCode);
        if (! $organizationEnvEntity) {
            ExceptionBuilder::throw(UserErrorCode::ORGANIZATION_NOT_EXIST);
        }

        // 3. 保存 magic_organization_code，origin_organization_code，environment_id，切换时间
        $organizationData = [
            'magic_organization_code' => $magicOrganizationCode,
            'third_organization_code' => $organizationEnvEntity->getOriginOrganizationCode(),
            'environment_id' => $organizationEnvEntity->getEnvironmentId(),
            'switch_time' => time(),
        ];

        $this->userSettingAppService->saveCurrentOrganizationDataByDelightfulId($magicId, $organizationData);
        return $organizationData;
    }

    /**
     * 获取账号下可用组织列表（仅包含启用状态组织）。
     *
     * @throws Throwable
     */
    public function getOrganizationsByAuthorization(string $authorization): DelightfulUserOrganizationListDTO
    {
        $userDetails = $this->userDomainService->getUsersDetailByAccountFromAuthorization($authorization);
        if (empty($userDetails)) {
            return new DelightfulUserOrganizationListDTO();
        }

        $organizationUserMap = [];
        $magicId = null;
        foreach ($userDetails as $detail) {
            $organizationCode = $detail->getOrganizationCode();
            if ($organizationCode === '') {
                continue;
            }

            if (! isset($organizationUserMap[$organizationCode])) {
                $organizationUserMap[$organizationCode] = $detail->getUserId();
            }

            if ($magicId === null) {
                $magicId = $detail->getDelightfulId();
            }
        }

        if ($magicId === null || empty($organizationUserMap)) {
            return new DelightfulUserOrganizationListDTO();
        }

        $organizations = $this->organizationDomainService->getByCodes(array_keys($organizationUserMap));
        if (empty($organizations)) {
            return new DelightfulUserOrganizationListDTO();
        }

        $currentOrganizationData = $this->getCurrentOrganizationCode($magicId) ?? [];
        $currentOrganizationCode = $currentOrganizationData['magic_organization_code'] ?? null;

        $listDTO = new DelightfulUserOrganizationListDTO();
        foreach ($organizations as $organizationCode => $organizationEntity) {
            if ($organizationEntity === null || ! $organizationEntity->isNormal()) {
                continue;
            }

            $userId = $organizationUserMap[$organizationCode] ?? null;
            if ($userId === null || $userId === '') {
                continue;
            }

            $dataIsolation = DataIsolation::create($organizationCode, $userId);
            $isAdmin = $this->organizationAdminDomainService->isOrganizationAdmin($dataIsolation, $userId);
            $isCreator = $this->organizationAdminDomainService->isOrganizationCreator($dataIsolation, $userId);

            $subscriptionInfo = $this->organizationProductResolver->resolveSubscriptionInfo($organizationCode, $userId);

            $item = new DelightfulUserOrganizationItemDTO([
                'magic_organization_code' => $organizationCode,
                'name' => $organizationEntity->getName(),
                'organization_type' => $organizationEntity->getType(),
                'logo' => $organizationEntity->getLogo(),
                'seats' => $organizationEntity->getSeats(),
                'is_current' => $organizationCode === $currentOrganizationCode,
                'is_admin' => $isAdmin,
                'is_creator' => $isCreator,
                'product_name' => $subscriptionInfo['product_name'] ?? null,
                'plan_type' => $subscriptionInfo['plan_type'] ?? null,
                'subscription_tier' => $subscriptionInfo['subscription_tier'] ?? null,
            ]);

            $listDTO->addItem($item);
        }

        return $listDTO;
    }
}
