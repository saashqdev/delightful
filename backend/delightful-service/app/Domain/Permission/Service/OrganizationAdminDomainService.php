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
     * queryorganization管理员list.
     * @return array{total: int, list: OrganizationAdminEntity[]}
     */
    public function queries(DataIsolation $dataIsolation, Page $page, ?array $filters = null): array
    {
        return $this->organizationAdminRepository->queries($dataIsolation, $page, $filters);
    }

    /**
     * saveorganization管理员.
     */
    public function save(DataIsolation $dataIsolation, OrganizationAdminEntity $savingOrganizationAdminEntity): OrganizationAdminEntity
    {
        if ($savingOrganizationAdminEntity->shouldCreate()) {
            $organizationAdminEntity = clone $savingOrganizationAdminEntity;
            $organizationAdminEntity->prepareForCreation();

            // checkuserwhether已经是organization管理员
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
     * getorganization管理员detail.
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
     * according touserIDgetorganization管理员.
     */
    public function getByUserId(DataIsolation $dataIsolation, string $userId): ?OrganizationAdminEntity
    {
        return $this->organizationAdminRepository->getByUserId($dataIsolation, $userId);
    }

    /**
     * deleteorganization管理员.
     */
    public function destroy(DataIsolation $dataIsolation, OrganizationAdminEntity $organizationAdminEntity): void
    {
        // indeleteorganization管理员record之前，先移except其inpermission系统中的 role_user associate
        try {
            // createpermission隔离object，useat操作roleservice
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

        // deleteorganization管理员record
        $this->organizationAdminRepository->delete($dataIsolation, $organizationAdminEntity);
    }

    /**
     * checkuserwhether为organization管理员.
     */
    public function isOrganizationAdmin(DataIsolation $dataIsolation, string $userId): bool
    {
        return $this->organizationAdminRepository->isOrganizationAdmin($dataIsolation, $userId);
    }

    /**
     * 授予userorganization管理员permission.
     */
    public function grant(DataIsolation $dataIsolation, string $userId, ?string $grantorUserId, ?string $remarks = null, bool $isOrganizationCreator = false): OrganizationAdminEntity
    {
        // organization校验与限制
        $orgCode = $dataIsolation->getCurrentOrganizationCode();
        $organization = $this->organizationRepository->getByCode($orgCode);
        if (! $organization) {
            $this->logger->warning('找nottoorganizationcode', ['organizationCode' => $orgCode]);
            ExceptionBuilder::throw(PermissionErrorCode::ORGANIZATION_NOT_EXISTS);
        }
        // 个人organizationnotallow授予organization管理员
        if ($organization->getType() === 1) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.personal_organization_cannot_grant_admin');
        }

        // checkuserwhether已经是organization管理员
        if ($this->isOrganizationAdmin($dataIsolation, $userId)) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.user_already_organization_admin', ['userId' => $userId]);
        }
        // checkuserwhethervalid
        $user = $this->userRepository->getUserById($userId);
        if (! $user) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST, 'user.not_exist', ['userId' => $userId]);
        }

        // 授予organization管理员实体
        $organizationAdmin = $this->organizationAdminRepository->grant($dataIsolation, $userId, $grantorUserId, $remarks, $isOrganizationCreator);

        // synccreate / updateorganization管理员role
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
     * undouserorganization管理员permission.
     */
    public function revoke(DataIsolation $dataIsolation, string $userId): void
    {
        // checkuserwhether为organization管理员
        if (! $this->isOrganizationAdmin($dataIsolation, $userId)) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.user_not_organization_admin', ['userId' => $userId]);
        }

        // checkwhether为organizationcreate人，organizationcreate人not可delete管理员permission
        $organizationAdmin = $this->getByUserId($dataIsolation, $userId);
        if ($organizationAdmin && $organizationAdmin->isOrganizationCreator()) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.organization_creator_cannot_be_revoked', ['userId' => $userId]);
        }

        $this->organizationAdminRepository->revoke($dataIsolation, $userId);

        // sync移exceptorganization管理员role
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
     * getorganization下所haveorganization管理员.
     */
    public function getAllOrganizationAdmins(DataIsolation $dataIsolation): array
    {
        return $this->organizationAdminRepository->getAllOrganizationAdmins($dataIsolation);
    }

    /**
     * 批量checkuserwhether为organization管理员.
     */
    public function batchCheckOrganizationAdmin(DataIsolation $dataIsolation, array $userIds): array
    {
        return $this->organizationAdminRepository->batchCheckOrganizationAdmin($dataIsolation, $userIds);
    }

    /**
     * 转让organizationcreate人身份.
     */
    public function transferOrganizationCreator(DataIsolation $dataIsolation, string $currentCreatorUserId, string $newCreatorUserId, string $operatorUserId): void
    {
        // checkcurrentcreate人whether存inandindeed是create人
        $currentCreator = $this->getByUserId($dataIsolation, $currentCreatorUserId);
        if (! $currentCreator || ! $currentCreator->isOrganizationCreator()) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.current_user_not_organization_creator', ['userId' => $currentCreatorUserId]);
        }

        // check新create人whether已经是organization管理员
        $newCreator = $this->getByUserId($dataIsolation, $newCreatorUserId);
        if (! $newCreator) {
            // if新create人alsonot是管理员，先授予管理员permission
            $newCreator = $this->grant($dataIsolation, $newCreatorUserId, $operatorUserId, '转让organizationcreate人身份时自动授予管理员permission');
        }

        // cancelcurrentcreate人的create人身份
        $currentCreator->unmarkAsOrganizationCreator();
        $currentCreator->prepareForModification();
        $this->organizationAdminRepository->save($dataIsolation, $currentCreator);

        // 授予新create人的create人身份
        $newCreator->markAsOrganizationCreator();
        $newCreator->prepareForModification();
        $this->organizationAdminRepository->save($dataIsolation, $newCreator);
    }

    /**
     * getorganizationcreate人.
     */
    public function getOrganizationCreator(DataIsolation $dataIsolation): ?OrganizationAdminEntity
    {
        return $this->organizationAdminRepository->getOrganizationCreator($dataIsolation);
    }

    /**
     * checkuserwhether为organizationcreate人.
     */
    public function isOrganizationCreator(DataIsolation $dataIsolation, string $userId): bool
    {
        $admin = $this->getByUserId($dataIsolation, $userId);
        return $admin && $admin->isOrganizationCreator();
    }
}
