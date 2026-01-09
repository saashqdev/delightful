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
 * assistant service.
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
     * optimizeversion:directlygetenableassistantversion,avoidpass inbigquantityID.
     * @return DelightfulAgentVersionEntity[]
     */
    public function getEnabledAgentsByOrganization(string $organizationCode, int $page, int $pageSize, string $agentName): array
    {
        return $this->agentVersionRepository->getEnabledAgentsByOrganization($organizationCode, $page, $pageSize, $agentName);
    }

    /**
     * optimizeversion:getenableassistanttotal.
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
        // approvalswitch todo
        $approvalOpen = false;
        $reviewOpen = false;

        $msg = '';
        // ifoldstatusalreadyalreadyisenterpriseorpersonmarket,thennotallowback
        $oldDelightfulAgentVersionEntity = $this->agentVersionRepository->getNewestAgentVersionEntity($delightfulAgentVersionEntity->getAgentId());
        if ($oldDelightfulAgentVersionEntity !== null) {
            $this->validateVersionNumber($delightfulAgentVersionEntity->getVersionNumber(), $oldDelightfulAgentVersionEntity->getVersionNumber());
            $this->validateReleaseScope($delightfulAgentVersionEntity->getReleaseScope(), $oldDelightfulAgentVersionEntity->getReleaseScope());
        }

        if ($delightfulAgentVersionEntity->getReleaseScope() === DelightfulAgentReleaseStatus::PERSONAL_USE->value) {
            // personuse
            $msg = 'publishsuccess';
        } elseif ($delightfulAgentVersionEntity->getReleaseScope() === DelightfulAgentReleaseStatus::PUBLISHED_TO_ENTERPRISE->value) {
            // publishtoenterpriseinsidedepartment
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
            // publishtoapplicationmarket
            // reviewswitch
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
     * according toidsgetassistantversion.
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

        // approvalswitch
        /* @phpstan-ignore-next-line */
        if ($approvalOpen) {
            // validationstatus
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
        // returnissemantic化version,needinreturnfoundationup+1
        $agentMaxVersion = $this->agentVersionRepository->getAgentMaxVersion($agentId);
        // ifversionnumberisintegerformat(like 1),willitsconvertforsemantic化versionnumber(like 1.0.0)
        if (is_numeric($agentMaxVersion) && strpos($agentMaxVersion, '.') === false) {
            $agentMaxVersion = $agentMaxVersion . '.0.0';
        }

        // parseversionnumber,for example "0.0.1" => ['0', '0', '1']
        [$major, $minor, $patch] = explode('.', $agentMaxVersion);

        // will PATCH departmentminuteadd 1
        $patch = (int) $patch + 1;

        // if PATCH 达to 10,enterpositionto MINOR(canaccording torequirementadjustthisrule)
        if ($patch > 99) {
            $patch = 0;
            $minor = (int) $minor + 1;
        }

        // if MINOR 达to 10,enterpositionto MAJOR(canaccording torequirementadjustthisrule)
        if ($minor > 99) {
            // notresetminor,whileisdirectly增bigmajor,avoidnotrequiredwantreset
            $minor = 0;
            $major = (int) $major + 1;
        }

        // spliceandreturnnewversionnumber
        return "{$major}.{$minor}.{$patch}";
    }

    /**
     * according toassistant id getdefaultversion.
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
     * based oncursorpaginationgetfingersetorganizationassistantversionlist.
     * @param string $organizationCode organizationcode
     * @param array $agentVersionIds assistantversionIDlist
     * @param string $cursor cursorID,ifforemptystringthenfrommostnewstart
     * @param int $pageSize eachpagequantity
     * @return array<DelightfulAgentVersionEntity>
     */
    public function getAgentsByOrganizationWithCursor(string $organizationCode, array $agentVersionIds, string $cursor, int $pageSize): array
    {
        $res = $this->agentVersionRepository->getAgentsByOrganizationWithCursor($organizationCode, $agentVersionIds, $cursor, $pageSize);
        return array_map(fn ($item) => new DelightfulAgentVersionEntity($item), $res);
    }

    /**
     * verifynewversionnumberwhetherlegal.
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
     * verifypublishrangewhetherlegal.
     */
    private function validateReleaseScope(int $newScope, int $oldScope): void
    {
        if ($newScope >= $oldScope) {
            return;
        }

        // checkwhether试graphfrommorehighlevelotherpublishrangebacktomorelowlevelother
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
