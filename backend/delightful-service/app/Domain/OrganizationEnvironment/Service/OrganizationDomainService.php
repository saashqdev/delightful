<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\OrganizationEnvironment\Entity\OrganizationEntity;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationRepositoryInterface;
use App\Domain\Permission\Service\OrganizationAdminDomainService;
use App\ErrorCode\PermissionErrorCode;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Throwable;

/**
 * organization领域service.
 */
readonly class OrganizationDomainService
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
        private DelightfulUserDomainService $userDomainService,
        private OrganizationAdminDomainService $organizationAdminDomainService
    ) {
    }

    /**
     * createorganization.
     */
    public function create(OrganizationEntity $organizationEntity): OrganizationEntity
    {
        // checkencodingwhetheralready存in
        if ($this->organizationRepository->existsByCode($organizationEntity->getDelightfulOrganizationCode())) {
            ExceptionBuilder::throw(PermissionErrorCode::ORGANIZATION_CODE_EXISTS);
        }

        // checkcreate者whether存in
        $creatorId = $organizationEntity->getCreatorId();
        if ($creatorId !== null) {
            $creator = $this->userDomainService->getUserById((string) $creatorId);
            if ($creator === null) {
                ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
            }
        }

        $organizationEntity->prepareForCreation();

        $savedOrganization = $this->organizationRepository->save($organizationEntity);

        if ($creatorId !== null && $savedOrganization->getType() !== 1) {
            // personorganizationnotaddorganizationadministrator
            // forcreate者addorganizationadministratorpermissionandmarkfororganizationcreateperson
            try {
                $dataIsolation = DataIsolation::simpleMake($savedOrganization->getDelightfulOrganizationCode(), (string) $creatorId);
                $this->organizationAdminDomainService->grant(
                    $dataIsolation,
                    (string) $creatorId,
                    (string) $creatorId, // 授予者alsoiscreate者from己
                    'organizationcreate者from动获administratorpermission',
                    true // markfororganizationcreateperson
                );
            } catch (Throwable $e) {
                // if授予administratorpermissionfail,recordlogbutnotimpactorganizationcreate
                error_log("Failed to grant organization admin permission for creator {$creatorId}: " . $e->getMessage());
            }
        }

        return $savedOrganization;
    }

    /**
     * updateorganization.
     */
    public function update(OrganizationEntity $organizationEntity): OrganizationEntity
    {
        if ($organizationEntity->shouldCreate()) {
            ExceptionBuilder::throw(PermissionErrorCode::ORGANIZATION_NOT_EXISTS);
        }

        // checkencodingwhetheralready存in(rowexceptcurrentorganization)
        if ($this->organizationRepository->existsByCode($organizationEntity->getDelightfulOrganizationCode(), $organizationEntity->getId())) {
            ExceptionBuilder::throw(PermissionErrorCode::ORGANIZATION_CODE_EXISTS);
        }

        $organizationEntity->prepareForModification();

        return $this->organizationRepository->save($organizationEntity);
    }

    /**
     * according toIDgetorganization.
     */
    public function getById(int $id): ?OrganizationEntity
    {
        return $this->organizationRepository->getById($id);
    }

    /**
     * according toencodinggetorganization.
     */
    public function getByCode(string $delightfulOrganizationCode): ?OrganizationEntity
    {
        return $this->organizationRepository->getByCode($delightfulOrganizationCode);
    }

    /**
     * according toencodinglistbatchquantitygetorganization.
     * @param string[] $delightfulOrganizationCodes
     * @return OrganizationEntity[]
     */
    public function getByCodes(array $delightfulOrganizationCodes): array
    {
        if (empty($delightfulOrganizationCodes)) {
            return [];
        }

        $entities = $this->organizationRepository->getByCodes($delightfulOrganizationCodes);

        $codeMapEntity = [];
        foreach ($entities as $entity) {
            $codeMapEntity[$entity->getDelightfulOrganizationCode()] = $entity;
        }
        return $codeMapEntity;
    }

    /**
     * according tonamegetorganization.
     */
    public function getByName(string $name): ?OrganizationEntity
    {
        return $this->organizationRepository->getByName($name);
    }

    /**
     * queryorganizationlist.
     * @return array{total: int, list: OrganizationEntity[]}
     */
    public function queries(Page $page, ?array $filters = null): array
    {
        return $this->organizationRepository->queries($page, $filters);
    }

    /**
     * deleteorganization.
     */
    public function delete(int $id): void
    {
        $organization = $this->getById($id);
        if (! $organization) {
            ExceptionBuilder::throw(PermissionErrorCode::ORGANIZATION_NOT_EXISTS);
        }

        $this->organizationRepository->delete($organization);
    }

    /**
     * enableorganization.
     */
    public function enable(int $id): OrganizationEntity
    {
        $organization = $this->getById($id);
        if (! $organization) {
            ExceptionBuilder::throw(PermissionErrorCode::ORGANIZATION_NOT_EXISTS);
        }

        $organization->enable();
        $organization->prepareForModification();

        return $this->organizationRepository->save($organization);
    }

    /**
     * disableorganization.
     */
    public function disable(int $id): OrganizationEntity
    {
        $organization = $this->getById($id);
        if (! $organization) {
            ExceptionBuilder::throw(PermissionErrorCode::ORGANIZATION_NOT_EXISTS);
        }

        $organization->disable();
        $organization->prepareForModification();

        return $this->organizationRepository->save($organization);
    }

    /**
     * checkorganizationencodingwhethercanuse.
     */
    public function isCodeAvailable(string $code, ?int $excludeId = null): bool
    {
        return ! $this->organizationRepository->existsByCode($code, $excludeId);
    }

    public function isPersonOrganization(string $code): bool
    {
        $organizationEntity = $this->organizationRepository->getByCode($code);
        return $organizationEntity->getType() == 1;
    }
}
