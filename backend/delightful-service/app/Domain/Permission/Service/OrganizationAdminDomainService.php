<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Repository\Facade\DelightfulUserRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationRepositoryInterface;
use App\Domain\Permission\Entity\OrganizationAdminEntity;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Domain\Permission\Repository\Facade\OrganizationAdminRepositoryInterface;
use App\ErrorCode\PermissionErrorCode;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class OrganizationAdminDomainService
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly OrganizationAdminRepositoryInterface $organizationAdminRepository,
        private readonly DelightfulUserRepositoryInterface $userRepository,
        private readonly RoleDomainService $roleDomainService,
        private readonly OrganizationRepositoryInterface $organizationRepository
    ) {
        $this->logger = ApplicationContext::getContainer()->get(LoggerFactory::class)?->get(static::class);
    }

    /**
     * queryorganizationadministratorlist.
     * @return array{total: int, list: OrganizationAdminEntity[]}
     */
    public function queries(DataIsolation $dataIsolation, Page $page, ?array $filters = null): array
    {
        return $this->organizationAdminRepository->queries($dataIsolation, $page, $filters);
    }

    /**
     * saveorganizationadministrator.
     */
    public function save(DataIsolation $dataIsolation, OrganizationAdminEntity $savingOrganizationAdminEntity): OrganizationAdminEntity
    {
        if ($savingOrganizationAdminEntity->shouldCreate()) {
            $organizationAdminEntity = clone $savingOrganizationAdminEntity;
            $organizationAdminEntity->prepareForCreation();

            // checkuserwhetheralreadyalreadyisorganizationadministrator
            if ($this->organizationAdminRepository->getByUserId($dataIsolation, $savingOrganizationAdminEntity->getUserId())) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.user_already_organization_admin', ['userId' => $savingOrganizationAdminEntity->getUserId()]);
            }
        } else {
            $organizationAdminEntity = $this->organizationAdminRepository->getById($dataIsolation, $savingOrganizationAdminEntity->getId());
            if (! $organizationAdminEntity) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.organization_admin_not_found', ['id' => $savingOrganizationAdminEntity->getId()]);
            }

            $savingOrganizationAdminEntity->prepareForModification();
            $organizationAdminEntity = $savingOrganizationAdminEntity;
        }

        return $this->organizationAdminRepository->save($dataIsolation, $organizationAdminEntity);
    }

    /**
     * getorganizationadministratordetail.
     */
    public function show(DataIsolation $dataIsolation, int $id): OrganizationAdminEntity
    {
        $organizationAdminEntity = $this->organizationAdminRepository->getById($dataIsolation, $id);
        if (! $organizationAdminEntity) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.organization_admin_not_found', ['id' => $id]);
        }
        return $organizationAdminEntity;
    }

    /**
     * according touserIDgetorganizationadministrator.
     */
    public function getByUserId(DataIsolation $dataIsolation, string $userId): ?OrganizationAdminEntity
    {
        return $this->organizationAdminRepository->getByUserId($dataIsolation, $userId);
    }

    /**
     * deleteorganizationadministrator.
     */
    public function destroy(DataIsolation $dataIsolation, OrganizationAdminEntity $organizationAdminEntity): void
    {
        // indeleteorganizationadministratorrecordoffront,move firstexceptitsinpermissionsystemmiddle role_user associate
        try {
            // createpermissionisolationobject,useatoperationasroleservice
            $permissionIsolation = PermissionDataIsolation::create(
                $dataIsolation->getCurrentOrganizationCode(),
                $dataIsolation->getCurrentUserId() ?? ''
            );

            $this->roleDomainService->removeOrganizationAdmin($permissionIsolation, $organizationAdminEntity->getUserId());
        } catch (Throwable $e) {
            $this->logger->error('Failed to remove organization admin role when destroying admin', [
                'exception' => $e,
            ]);
        }

        // deleteorganizationadministratorrecord
        $this->organizationAdminRepository->delete($dataIsolation, $organizationAdminEntity);
    }

    /**
     * checkuserwhetherfororganizationadministrator.
     */
    public function isOrganizationAdmin(DataIsolation $dataIsolation, string $userId): bool
    {
        return $this->organizationAdminRepository->isOrganizationAdmin($dataIsolation, $userId);
    }

    /**
     * grantuserorganizationadministratorpermission.
     */
    public function grant(DataIsolation $dataIsolation, string $userId, ?string $grantorUserId, ?string $remarks = null, bool $isOrganizationCreator = false): OrganizationAdminEntity
    {
        // organizationvalidationandlimit
        $orgCode = $dataIsolation->getCurrentOrganizationCode();
        $organization = $this->organizationRepository->getByCode($orgCode);
        if (! $organization) {
            $this->logger->warning('findnottoorganizationcode', ['organizationCode' => $orgCode]);
            ExceptionBuilder::throw(PermissionErrorCode::ORGANIZATION_NOT_EXISTS);
        }
        // personorganizationnotallowgrantorganizationadministrator
        if ($organization->getType() === 1) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.personal_organization_cannot_grant_admin');
        }

        // checkuserwhetheralreadyalreadyisorganizationadministrator
        if ($this->isOrganizationAdmin($dataIsolation, $userId)) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.user_already_organization_admin', ['userId' => $userId]);
        }
        // checkuserwhethervalid
        $user = $this->userRepository->getUserById($userId);
        if (! $user) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST, 'user.not_exist', ['userId' => $userId]);
        }

        // grantorganizationadministratoractualbody
        $organizationAdmin = $this->organizationAdminRepository->grant($dataIsolation, $userId, $grantorUserId, $remarks, $isOrganizationCreator);

        // synccreate / updateorganizationadministratorrole
        try {
            $permissionIsolation = PermissionDataIsolation::create(
                $dataIsolation->getCurrentOrganizationCode(),
                $dataIsolation->getCurrentUserId() ?? ''
            );
            $this->roleDomainService->addOrganizationAdmin($permissionIsolation, [$userId]);
        } catch (Throwable $e) {
            $this->logger->warning('Failed to add organization admin role', [
                'exception' => $e,
                'userId' => $userId,
                'organizationCode' => $dataIsolation->getCurrentOrganizationCode(),
            ]);
        }

        return $organizationAdmin;
    }

    /**
     * undouserorganizationadministratorpermission.
     */
    public function revoke(DataIsolation $dataIsolation, string $userId): void
    {
        // checkuserwhetherfororganizationadministrator
        if (! $this->isOrganizationAdmin($dataIsolation, $userId)) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.user_not_organization_admin', ['userId' => $userId]);
        }

        // checkwhetherfororganizationcreateperson,organizationcreatepersonnotcandeleteadministratorpermission
        $organizationAdmin = $this->getByUserId($dataIsolation, $userId);
        if ($organizationAdmin && $organizationAdmin->isOrganizationCreator()) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.organization_creator_cannot_be_revoked', ['userId' => $userId]);
        }

        $this->organizationAdminRepository->revoke($dataIsolation, $userId);

        // syncmoveexceptorganizationadministratorrole
        try {
            $permissionIsolation = PermissionDataIsolation::create(
                $dataIsolation->getCurrentOrganizationCode(),
                $dataIsolation->getCurrentUserId() ?? ''
            );
            $this->roleDomainService->removeOrganizationAdmin($permissionIsolation, $userId);
        } catch (Throwable $e) {
            $this->logger->warning('Failed to remove organization admin role', [
                'exception' => $e,
                'userId' => $userId,
                'organizationCode' => $dataIsolation->getCurrentOrganizationCode(),
            ]);
        }
    }

    /**
     * getorganizationdown haveorganizationadministrator.
     */
    public function getAllOrganizationAdmins(DataIsolation $dataIsolation): array
    {
        return $this->organizationAdminRepository->getAllOrganizationAdmins($dataIsolation);
    }

    /**
     * batchquantitycheckuserwhetherfororganizationadministrator.
     */
    public function batchCheckOrganizationAdmin(DataIsolation $dataIsolation, array $userIds): array
    {
        return $this->organizationAdminRepository->batchCheckOrganizationAdmin($dataIsolation, $userIds);
    }

    /**
     * transferletorganizationcreateperson身share.
     */
    public function transferOrganizationCreator(DataIsolation $dataIsolation, string $currentCreatorUserId, string $newCreatorUserId, string $operatorUserId): void
    {
        // checkcurrentcreatepersonwhetherexistsinandindeediscreateperson
        $currentCreator = $this->getByUserId($dataIsolation, $currentCreatorUserId);
        if (! $currentCreator || ! $currentCreator->isOrganizationCreator()) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.current_user_not_organization_creator', ['userId' => $currentCreatorUserId]);
        }

        // checknewcreatepersonwhetheralreadyalreadyisorganizationadministrator
        $newCreator = $this->getByUserId($dataIsolation, $newCreatorUserId);
        if (! $newCreator) {
            // ifnewcreatepersonalsonotisadministrator,firstgrantadministratorpermission
            $newCreator = $this->grant($dataIsolation, $newCreatorUserId, $operatorUserId, 'transferletorganizationcreateperson身shareo clockfromautograntadministratorpermission');
        }

        // cancelcurrentcreatepersoncreateperson身share
        $currentCreator->unmarkAsOrganizationCreator();
        $currentCreator->prepareForModification();
        $this->organizationAdminRepository->save($dataIsolation, $currentCreator);

        // grantnewcreatepersoncreateperson身share
        $newCreator->markAsOrganizationCreator();
        $newCreator->prepareForModification();
        $this->organizationAdminRepository->save($dataIsolation, $newCreator);
    }

    /**
     * getorganizationcreateperson.
     */
    public function getOrganizationCreator(DataIsolation $dataIsolation): ?OrganizationAdminEntity
    {
        return $this->organizationAdminRepository->getOrganizationCreator($dataIsolation);
    }

    /**
     * checkuserwhetherfororganizationcreateperson.
     */
    public function isOrganizationCreator(DataIsolation $dataIsolation, string $userId): bool
    {
        $admin = $this->getByUserId($dataIsolation, $userId);
        return $admin && $admin->isOrganizationCreator();
    }
}
