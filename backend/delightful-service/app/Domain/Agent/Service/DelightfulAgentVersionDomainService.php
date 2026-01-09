<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Service;

use App\Domain\Agent\Constant\DelightfulAgentReleaseStatus;
use App\Domain\Agent\Constant\DelightfulAgentVersionStatus;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Agent\Repository\Persistence\DelightfulAgentRepository;
use App\Domain\Agent\Repository\Persistence\DelightfulAgentVersionRepository;
use App\Domain\Contact\Repository\Persistence\DelightfulUserRepository;
use App\Domain\Flow\Repository\Facade\DelightfulFlowVersionRepositoryInterface;
use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

/**
 * 助理 service.
 */
class DelightfulAgentVersionDomainService
{
    public function __construct(
        public DelightfulAgentVersionRepository $agentVersionRepository,
        public DelightfulAgentRepository $agentRepository,
        public DelightfulUserRepository $delightfulUserRepository,
        public DelightfulFlowVersionRepositoryInterface $delightfulFlowVersionRepository
    ) {
    }

    /**
     * @return DelightfulAgentVersionEntity[]
     */
    public function getAgentsByOrganization(string $organizationCode, array $agentIds, int $page, int $pageSize, string $agentName, ?string $descriptionKeyword = null): array
    {
        return $this->agentVersionRepository->getAgentsByOrganization($organizationCode, $agentIds, $page, $pageSize, $agentName, $descriptionKeyword);
    }

    public function getAgentsByOrganizationCount(string $organizationCode, array $agentIds, string $agentName): int
    {
        return $this->agentVersionRepository->getAgentsByOrganizationCount($organizationCode, $agentIds, $agentName);
    }

    /**
     * optimizeversion：直接getenable的助理version，避免传入大量ID.
     * @return DelightfulAgentVersionEntity[]
     */
    public function getEnabledAgentsByOrganization(string $organizationCode, int $page, int $pageSize, string $agentName): array
    {
        return $this->agentVersionRepository->getEnabledAgentsByOrganization($organizationCode, $page, $pageSize, $agentName);
    }

    /**
     * optimizeversion：getenable助理的total.
     */
    public function getEnabledAgentsByOrganizationCount(string $organizationCode, string $agentName): int
    {
        return $this->agentVersionRepository->getEnabledAgentsByOrganizationCount($organizationCode, $agentName);
    }

    public function getAgentsFromMarketplace(array $agentIds, int $page, int $pageSize): array
    {
        return $this->agentVersionRepository->getAgentsFromMarketplace($agentIds, $page, $pageSize);
    }

    public function getAgentsFromMarketplaceCount(array $agentIds): int
    {
        return $this->agentVersionRepository->getAgentsFromMarketplaceCount($agentIds);
    }

    /**
     * publishversion.
     */
    public function releaseAgentVersion(DelightfulAgentVersionEntity $delightfulAgentVersionEntity): array
    {
        // approval开关 todo
        $approvalOpen = false;
        $reviewOpen = false;

        $msg = '';
        // 如果旧status已经是企业或者市场，则不allow回退
        $oldDelightfulAgentVersionEntity = $this->agentVersionRepository->getNewestAgentVersionEntity($delightfulAgentVersionEntity->getAgentId());
        if ($oldDelightfulAgentVersionEntity !== null) {
            $this->validateVersionNumber($delightfulAgentVersionEntity->getVersionNumber(), $oldDelightfulAgentVersionEntity->getVersionNumber());
            $this->validateReleaseScope($delightfulAgentVersionEntity->getReleaseScope(), $oldDelightfulAgentVersionEntity->getReleaseScope());
        }

        if ($delightfulAgentVersionEntity->getReleaseScope() === DelightfulAgentReleaseStatus::PERSONAL_USE->value) {
            // 个人use
            $msg = 'publishsuccess';
        } elseif ($delightfulAgentVersionEntity->getReleaseScope() === DelightfulAgentReleaseStatus::PUBLISHED_TO_ENTERPRISE->value) {
            // publish到企业内部
            /* @phpstan-ignore-next-line */
            if ($approvalOpen) {
                $delightfulAgentVersionEntity->setApprovalStatus(DelightfulAgentVersionStatus::APPROVAL_PENDING->value);
                $delightfulAgentVersionEntity->setEnterpriseReleaseStatus(DelightfulAgentVersionStatus::APP_MARKET_LISTED->value);
                $msg = 'submitsuccess';
            } else {
                $delightfulAgentVersionEntity->setEnterpriseReleaseStatus(DelightfulAgentVersionStatus::ENTERPRISE_PUBLISHED->value);
            }
            $msg = 'publishsuccess';
        } elseif ($delightfulAgentVersionEntity->getReleaseScope() === DelightfulAgentReleaseStatus::PUBLISHED_TO_MARKET->value) {
            // publish到application市场
            // 审核开关
            /* @phpstan-ignore-next-line */
            if ($reviewOpen) {
            } else {
                $delightfulAgentVersionEntity->setAppMarketStatus(DelightfulAgentVersionStatus::APP_MARKET_LISTED->value);
            }
        }

        $delightfulAgentVersionEntity = $this->agentVersionRepository->insert($delightfulAgentVersionEntity);

        return ['msg' => $msg, 'data' => $delightfulAgentVersionEntity];
    }

    public function getAgentById(string $id): DelightfulAgentVersionEntity
    {
        return $this->agentVersionRepository->getAgentById($id);
    }

    /**
     * according toidsget助理version.
     * @return array<DelightfulAgentVersionEntity>
     */
    public function getAgentByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return $this->agentVersionRepository->getAgentByIds($ids);
    }

    /**
     * @return DelightfulAgentVersionEntity[]
     */
    public function getReleaseAgentVersions(string $agentId): array
    {
        return $this->agentVersionRepository->getReleaseAgentVersions($agentId);
    }

    public function enableAgentVersionById(string $id): bool
    {
        $agent = $this->agentVersionRepository->getAgentById($id);

        $approvalOpen = false;

        // approval开关
        /* @phpstan-ignore-next-line */
        if ($approvalOpen) {
            // 校验status
            if ($agent->getApprovalStatus() !== DelightfulAgentVersionStatus::APPROVAL_PASSED->value) {
                ExceptionBuilder::throw(AgentErrorCode::VERSION_CAN_ONLY_BE_ENABLED_AFTER_APPROVAL);
            }
        }

        $this->agentVersionRepository->setEnterpriseStatus($id, DelightfulAgentVersionStatus::ENTERPRISE_ENABLED->value);

        return true;
    }

    public function disableAgentVersion($id): bool
    {
        $agent = $this->agentVersionRepository->getAgentById($id);

        if ($agent->getEnterpriseReleaseStatus() !== DelightfulAgentVersionStatus::ENTERPRISE_ENABLED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VERSION_ONLY_ENABLED_CAN_BE_DISABLED);
        }

        $this->agentVersionRepository->setEnterpriseStatus($id, DelightfulAgentVersionStatus::ENTERPRISE_DISABLED->value);

        return true;
    }

    public function getAgentMaxVersion(string $agentId): string
    {
        // return的是语义化version，need在return的基础上+1
        $agentMaxVersion = $this->agentVersionRepository->getAgentMaxVersion($agentId);
        // 如果version号是整数format（如 1），将其convert为语义化version号（如 1.0.0）
        if (is_numeric($agentMaxVersion) && strpos($agentMaxVersion, '.') === false) {
            $agentMaxVersion = $agentMaxVersion . '.0.0';
        }

        // parseversion号，for example "0.0.1" => ['0', '0', '1']
        [$major, $minor, $patch] = explode('.', $agentMaxVersion);

        // 将 PATCH 部分加 1
        $patch = (int) $patch + 1;

        // 如果 PATCH 达到 10，进位到 MINOR（canaccording to需求调整此规则）
        if ($patch > 99) {
            $patch = 0;
            $minor = (int) $minor + 1;
        }

        // 如果 MINOR 达到 10，进位到 MAJOR（canaccording to需求调整此规则）
        if ($minor > 99) {
            // 不resetminor，而是直接增大major，避免不必要的reset
            $minor = 0;
            $major = (int) $major + 1;
        }

        // 拼接并returnnewversion号
        return "{$major}.{$minor}.{$patch}";
    }

    /**
     * according to助理 id getdefault的version.
     */
    public function getDefaultVersions(array $agentIds): void
    {
        $this->agentVersionRepository->getDefaultVersions($agentIds);
    }

    /**
     * @return DelightfulAgentVersionEntity[]
     */
    public function listAgentVersionsByIds(array $agentVersionIds): array
    {
        return $this->agentVersionRepository->listAgentVersionsByIds($agentVersionIds);
    }

    public function getById(string $agentVersionId): DelightfulAgentVersionEntity
    {
        return $this->agentVersionRepository->getAgentById($agentVersionId);
    }

    public function updateAgentEnterpriseStatus(string $agentVersionId, int $status): void
    {
        $this->agentVersionRepository->updateAgentEnterpriseStatus($agentVersionId, $status);
    }

    public function getAgentByFlowCode(string $flowCode): ?DelightfulAgentVersionEntity
    {
        return $this->agentVersionRepository->getAgentByFlowCode($flowCode);
    }

    /**
     * based on游标paginationget指定organization的助理versionlist.
     * @param string $organizationCode organizationcode
     * @param array $agentVersionIds 助理versionIDlist
     * @param string $cursor 游标ID，如果为空string则从最新开始
     * @param int $pageSize 每页quantity
     * @return array<DelightfulAgentVersionEntity>
     */
    public function getAgentsByOrganizationWithCursor(string $organizationCode, array $agentVersionIds, string $cursor, int $pageSize): array
    {
        $res = $this->agentVersionRepository->getAgentsByOrganizationWithCursor($organizationCode, $agentVersionIds, $cursor, $pageSize);
        return array_map(fn ($item) => new DelightfulAgentVersionEntity($item), $res);
    }

    /**
     * verify新version号是否legal.
     * @throws BusinessException
     */
    private function validateVersionNumber(string $newVersion, string $oldVersion): void
    {
        if (version_compare($newVersion, $oldVersion, '<=')) {
            ExceptionBuilder::throw(
                AgentErrorCode::VALIDATE_FAILED,
                'agent.newly_published_version_number_cannot_be_same_as_previous_version_and_cannot_be_less_than_max_version_number'
            );
        }
    }

    /**
     * verifypublishrange是否legal.
     */
    private function validateReleaseScope(int $newScope, int $oldScope): void
    {
        if ($newScope >= $oldScope) {
            return;
        }

        // check是否试图从更高级别的publishrange回退到更低级别
        $errorMessage = match ($oldScope) {
            DelightfulAgentReleaseStatus::PUBLISHED_TO_ENTERPRISE->value => 'agent.already_published_to_enterprise_cannot_publish_to_individual',
            DelightfulAgentReleaseStatus::PUBLISHED_TO_MARKET->value => 'agent.already_published_to_market_cannot_publish_to_individual',
            default => null,
        };

        if ($errorMessage !== null) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, $errorMessage);
        }
    }
}
