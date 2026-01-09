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
 * userwhenfrontorganizationapplicationservice
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
     * getuserwhenfrontorganizationcode
     */
    public function getCurrentOrganizationCode(string $delightfulId): ?array
    {
        return $this->userSettingAppService->getCurrentOrganizationDataByDelightfulId($delightfulId);
    }

    /**
     * settinguserwhenfrontorganizationcode
     */
    public function setCurrentOrganizationCode(string $delightfulId, string $delightfulOrganizationCode): array
    {
        // 1. queryuserwhetherinfinger定organizationmiddle
        $userOrganizations = $this->userDomainService->getUserOrganizationsByDelightfulId($delightfulId);
        if (! in_array($delightfulOrganizationCode, $userOrganizations, true)) {
            ExceptionBuilder::throw(UserErrorCode::ORGANIZATION_NOT_EXIST);
        }

        // 2. query这organization相closeinformation：delightful_organizations_environment
        $organizationEnvEntity = $this->organizationEnvDomainService->getOrganizationEnvironmentByDelightfulOrganizationCode($delightfulOrganizationCode);
        if (! $organizationEnvEntity) {
            ExceptionBuilder::throw(UserErrorCode::ORGANIZATION_NOT_EXIST);
        }

        // 3. save delightful_organization_code，origin_organization_code，environment_id，切换time
        $organizationData = [
            'delightful_organization_code' => $delightfulOrganizationCode,
            'third_organization_code' => $organizationEnvEntity->getOriginOrganizationCode(),
            'environment_id' => $organizationEnvEntity->getEnvironmentId(),
            'switch_time' => time(),
        ];

        $this->userSettingAppService->saveCurrentOrganizationDataByDelightfulId($delightfulId, $organizationData);
        return $organizationData;
    }

    /**
     * get账numberdowncanuseorganizationcolumn表（仅containenabled statusorganization）。
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
        $delightfulId = null;
        foreach ($userDetails as $detail) {
            $organizationCode = $detail->getOrganizationCode();
            if ($organizationCode === '') {
                continue;
            }

            if (! isset($organizationUserMap[$organizationCode])) {
                $organizationUserMap[$organizationCode] = $detail->getUserId();
            }

            if ($delightfulId === null) {
                $delightfulId = $detail->getDelightfulId();
            }
        }

        if ($delightfulId === null || empty($organizationUserMap)) {
            return new DelightfulUserOrganizationListDTO();
        }

        $organizations = $this->organizationDomainService->getByCodes(array_keys($organizationUserMap));
        if (empty($organizations)) {
            return new DelightfulUserOrganizationListDTO();
        }

        $currentOrganizationData = $this->getCurrentOrganizationCode($delightfulId) ?? [];
        $currentOrganizationCode = $currentOrganizationData['delightful_organization_code'] ?? null;

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
                'delightful_organization_code' => $organizationCode,
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
