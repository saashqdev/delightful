<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\Share\Service;

use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Application\Share\Factory\ShareableResourceFactory;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Constant\ShareAccessType;
use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\ErrorCode\ShareErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\AccessTokenUtil;
use Delightful\BeDelightful\Infrastructure\Utils\PasswordCrypt;
use Delightful\BeDelightful\Interfaces\Share\Assembler\ShareAssembler;
use Delightful\BeDelightful\Interfaces\Share\DTO\Request\CreateShareRequestDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Request\GetShareDetailDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Request\ResourceListRequestDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Response\ShareItemDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Response\ShareItemWithPasswordDTO;
use Exception;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Resource sharing application service.
 */
class ResourceShareAppService extends AbstractShareAppService
{
    protected LoggerInterface $logger;

    public function __construct(
        private ShareableResourceFactory $resourceFactory,
        private ResourceShareDomainService $shareDomainService,
        private ShareAssembler $shareAssembler,
        public readonly LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    /**
     * Create share.
     *
     * @param DelightfulUserAuthorization $userAuthorization Current user
     * @param CreateShareRequestDTO $dto Create share request DTO
     * @return ShareItemDTO Share item DTO
     * @throws Exception Exception when creating share
     */
    public function createShare(DelightfulUserAuthorization $userAuthorization, CreateShareRequestDTO $dto): ShareItemDTO
    {
        $resourceId = $dto->resourceId;
        $userId = $userAuthorization->getId();
        $organizationCode = $userAuthorization->getOrganizationCode();

        // Validate resource type
        $resourceType = ResourceType::from($dto->resourceType);

        // 1. Get corresponding type resource factory
        try {
            $factory = $this->resourceFactory->create($resourceType);
        } catch (RuntimeException $e) {
            // Use ExceptionBuilder to throw unsupported resource type exception
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported', [$resourceType->name]);
        }

        // 2. Verify resource exists and is shareable
        if (! $factory->isResourceShareable($resourceId, $organizationCode)) {
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.resource_not_found_or_not_shareable', [$resourceId]);
        }

        // 3. Verify resource owner permission
        if (! $factory->hasSharePermission($resourceId, $userId, $organizationCode)) {
            ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.no_permission_to_share', [$resourceId]);
        }

        // 4. Save share (create or update) - use domain service
        $attributes = [
            'resource_name' => $factory->getResourceName($resourceId),
            'share_type' => $dto->shareType ?? ShareAccessType::Internet->value,
        ];

        try {
            $savedEntity = $this->shareDomainService->saveShare(
                $resourceId,
                $resourceType->value,
                $userId,
                $organizationCode,
                $attributes,
                $dto->password,
                $dto->expireDays
            );
        } catch (Exception $e) {
            $this->logger->error('Failed to save topic share, result: ' . $e->getMessage());
            ExceptionBuilder::throw(ShareErrorCode::CREATE_RESOURCES_ERROR, 'share.create_resources_error', [$resourceId]);
        }

        // 5. Build response
        return $this->shareAssembler->toDto($savedEntity);
    }

    /**
     * Cancel share.
     *
     * @param DelightfulUserAuthorization $userAuthorization Current user
     * @param int $shareId Share ID
     * @return bool Whether successful
     * @throws Exception Exception when canceling share
     */
    public function cancelShare(DelightfulUserAuthorization $userAuthorization, int $shareId): bool
    {
        $userId = $userAuthorization->getId();
        $organizationCode = $userAuthorization->getOrganizationCode();

        // Call domain service cancel share method
        return $this->shareDomainService->cancelShare($shareId, $userId, $organizationCode);
    }

    /**
     * @throws Exception
     */
    public function cancelShareByResourceId(DelightfulUserAuthorization $userAuthorization, string $resourceId): bool
    {
        $userId = $userAuthorization->getId();
        $organizationCode = $userAuthorization->getOrganizationCode();

        $shareEntity = $this->shareDomainService->getShareByResourceId($resourceId);
        if (is_null($shareEntity)) {
            return false;
        }

        // Validate resource type
        $resourceType = ResourceType::from($shareEntity->getResourceType());

        // 1. Get corresponding type resource factory
        try {
            $factory = $this->resourceFactory->create($resourceType);
        } catch (RuntimeException $e) {
            // Use ExceptionBuilder to throw unsupported resource type exception
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported', [$resourceType->name]);
        }

        // 2. Verify resource owner permission
        if (! $factory->hasSharePermission($resourceId, $userId, $organizationCode)) {
            ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.no_permission_to_share', [$resourceId]);
        }

        $shareEntity->setDeletedAt(date('Y-m-d H:i:s'));
        $shareEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $shareEntity->setUpdatedUid($userId);

        // Call domain service cancel share method
        $this->shareDomainService->saveShareByEntity($shareEntity);
        return true;
    }

    public function checkShare(?DelightfulUserAuthorization $userAuthorization, string $shareCode): array
    {
        $shareEntity = $this->shareDomainService->getShareByCode($shareCode);
        if (empty($shareEntity)) {
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.not_found', [$shareCode]);
        }
        return [
            'has_password' => ! empty($shareEntity->getPassword()),
            'user_id' => ! is_null($userAuthorization) ? $userAuthorization->getId() : '',
        ];
    }

    public function getShareDetail(?DelightfulUserAuthorization $userAuthorization, string $shareCode, GetShareDetailDTO $detailDTO): array
    {
        // First get detail content
        $shareEntity = $this->shareDomainService->getShareByCode($shareCode);
        if (empty($shareEntity)) {
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.not_found', [$shareCode]);
        }

        // Verify permission, currently only individuals have permission control
        if ($shareEntity->getShareType() == ShareAccessType::SelfOnly->value
            && ($userAuthorization === null || $shareEntity->getCreatedUid() != $userAuthorization->getId())) {
            ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.permission_denied', [$shareCode]);
        }

        // Verify password is correct
        if (! empty($shareEntity->getPassword()) && ($detailDTO->getPassword() != PasswordCrypt::decrypt($shareEntity->getPassword()))) {
            ExceptionBuilder::throw(ShareErrorCode::PASSWORD_ERROR, 'share.password_error', [$shareCode]);
        }

        // Call factory class to get share content data
        try {
            $resourceType = ResourceType::tryFrom($shareEntity->getResourceType());
            $factory = $this->resourceFactory->create($resourceType);
        } catch (RuntimeException $e) {
            // Use ExceptionBuilder to throw unsupported resource type exception
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported', [$resourceType->name]);
        }

        // Return data
        return [
            'resource_type' => $resourceType,
            'resource_name' => $shareEntity->getResourceName(),
            'temporary_token' => AccessTokenUtil::generate((string) $shareEntity->getId(), $shareEntity->getOrganizationCode()),
            'data' => $factory->getResourceContent(
                $shareEntity->getResourceId(),
                $shareEntity->getCreatedUid(),
                $shareEntity->getOrganizationCode(),
                $detailDTO->getPage(),
                $detailDTO->getPageSize()
            ),
        ];
    }

    public function getShareList(DelightfulUserAuthorization $userAuthorization, ResourceListRequestDTO $dto): array
    {
        $conditions = ['created_uid' => $userAuthorization->getId()];
        if (! empty($dto->getKeyword())) {
            $conditions['keyword'] = $dto->getKeyword();
        }
        $result = $this->shareDomainService->getShareList($dto->getPage(), $dto->getPageSize(), $conditions);

        if (empty($result)) {
            return [
                'total' => 0,
                'list' => [],
            ];
        }

        // Extend information
        try {
            $resourceType = ResourceType::from($dto->getResourceType());
            $factory = $this->resourceFactory->create($resourceType);
        } catch (RuntimeException $e) {
            // Use ExceptionBuilder to throw unsupported resource type exception
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported', [$resourceType->name]);
        }
        $result['list'] = $factory->getResourceExtendList($result['list']);
        return $result;
    }

    /**
     * Get share information by share code (without password).
     *
     * @param null|DelightfulUserAuthorization $userAuthorization Current user (can be null)
     * @param string $shareCode Share code
     * @return ShareItemDTO Share item DTO
     * @throws Exception Exception when getting share information
     */
    public function getShareByCode(?DelightfulUserAuthorization $userAuthorization, string $shareCode): ShareItemDTO
    {
        // Get and validate entity
        $shareEntity = $this->getAndValidateShareEntity($userAuthorization, $shareCode);

        // Use assembler to create basic DTO (without password)
        return $this->shareAssembler->toDto($shareEntity);
    }

    /**
     * Get share information by share code (with plaintext password).
     * Note: This method should only be used in specific scenarios, handle the returned password information carefully.
     *
     * @param null|DelightfulUserAuthorization $userAuthorization Current user (can be null)
     * @param string $shareCode Share code
     * @return ShareItemWithPasswordDTO Share item DTO containing password
     * @throws Exception Exception when getting share information
     */
    public function getShareWithPasswordByCode(?DelightfulUserAuthorization $userAuthorization, string $shareCode): ShareItemWithPasswordDTO
    {
        // Get and validate entity
        try {
            $shareEntity = $this->getAndValidateShareEntity($userAuthorization, $shareCode);
            //            if ($shareEntity->getCreatedUid() !== $userAuthorization->getId()) {
            //                ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.permission_denied', [$shareCode]);
            //            }
            // Use assembler to create DTO with password
            return $this->shareAssembler->toDtoWithPassword($shareEntity);
        } catch (BusinessException $e) {
            return new ShareItemWithPasswordDTO();
        }
    }

    /**
     * Get share entity by share code.
     *
     * @param string $shareCode Share code
     * @return null|ResourceShareEntity Share entity, or null if not found
     */
    public function getShare(string $shareCode): ?ResourceShareEntity
    {
        return $this->shareDomainService->getShareByCode($shareCode);
    }

    /**
     * Get and validate share entity.
     *
     * @param null|DelightfulUserAuthorization $userAuthorization Current user (can be null)
     * @param string $shareCode Share code
     * @return ResourceShareEntity Validated share entity
     * @throws Exception If validation fails
     */
    private function getAndValidateShareEntity(?DelightfulUserAuthorization $userAuthorization, string $shareCode): ResourceShareEntity
    {
        // Get share entity through domain service
        $shareEntity = $this->shareDomainService->getShareByCode($shareCode);

        // Verify share exists
        if (empty($shareEntity)) {
            ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.not_found', [$shareCode]);
        }

        // Verify permission, if share type is self-only, need to verify user identity
        if ($shareEntity->getShareType() == ShareAccessType::SelfOnly->value
            && ($userAuthorization === null || $shareEntity->getCreatedUid() != $userAuthorization->getId())) {
            ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.permission_denied', [$shareCode]);
        }

        return $shareEntity;
    }
}
