<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Share\Service;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
use Delightful\BeDelightful\Domain\Share\Repository\Facade\ResourceShareRepositoryInterface;
use Delightful\BeDelightful\ErrorCode\ShareErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\PasswordCrypt;
use Delightful\BeDelightful\Infrastructure\Utils\ShareCodeGenerator;
use Exception;

/**
 * Resource share domain service.
 */
class ResourceShareDomainService
{
    public function __construct(
        protected ResourceShareRepositoryInterface $shareRepository
    ) {
    }

    public function saveShareByEntity(ResourceShareEntity $shareEntity): ResourceShareEntity
    {
        try {
            return $this->shareRepository->save($shareEntity);
        } catch (Exception $e) {
            // Re-throw exception
            ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED, 'share.cancel_failed: ' . $shareEntity->getId());
        }
    }

    /**
     * Cancel share (logical delete).
     *
     * @param int $shareId Share ID
     * @param string $userId User ID
     * @param string $organizationCode Organization code
     * @return bool Whether cancel was successful
     * @throws Exception If cancel share fails
     */
    public function cancelShare(int $shareId, string $userId, string $organizationCode): bool
    {
        // 1. Get share entity
        $shareEntity = $this->shareRepository->getShareById($shareId);

        // 2. Verify share exists
        if (! $shareEntity) {
            ExceptionBuilder::throw(ShareErrorCode::NOT_FOUND, 'share.not_found', [$shareId]);
        }

        // 3. Verify permission to cancel share (only creator or admin can cancel)
        if ($shareEntity->getCreatedUid() !== $userId) {
            // Here can add additional permission check, e.g. check if user is admin
            ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.no_permission_to_cancel', [$shareId]);
        }

        // 4. Verify organization matches
        if ($shareEntity->getOrganizationCode() !== $organizationCode) {
            ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.organization_mismatch', [$shareId]);
        }

        // 5. Set deletion time and update info
        $shareEntity->setDeletedAt(date('Y-m-d H:i:s'));
        $shareEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $shareEntity->setUpdatedUid($userId);

        // 6. Save entity
        try {
            $this->shareRepository->save($shareEntity);
            return true;
        } catch (Exception $e) {
            // Re-throw exception
            ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED, 'share.cancel_failed', [$shareId]);
        }
    }

    /**
     * Get share details.
     *
     * @param string $resourceId Resource ID
     * @return null|ResourceShareEntity Share entity
     */
    public function getShareByResourceId(string $resourceId): ?ResourceShareEntity
    {
        return $this->shareRepository->getShareByResourceId($resourceId);
    }

    public function getShareByCode(string $code): ?ResourceShareEntity
    {
        return $this->shareRepository->getShareByCode($code);
    }

    /**
     * Get valid share
     * Valid share is a share that is not deleted and not expired.
     *
     * @param string $shareId Share ID
     * @return null|ResourceShareEntity Share entity
     */
    public function getValidShareById(string $shareId): ?ResourceShareEntity
    {
        $share = $this->shareRepository->getShareById((int) $shareId);

        if (! $share || ! $share->isValid()) {
            return null;
        }

        return $share;
    }

    /**
     * Get valid share by share code.
     *
     * @param string $shareCode Share code
     * @return null|ResourceShareEntity Share entity
     */
    public function getValidShareByCode(string $shareCode): ?ResourceShareEntity
    {
        $share = $this->shareRepository->getShareByCode($shareCode);

        if (! $share || ! $share->isValid()) {
            return null;
        }

        return $share;
    }

    /**
     * Increment share view count.
     *
     * @param string $shareId Share ID
     * @return bool Whether successful
     */
    public function incrementViewCount(string $shareId): bool
    {
        $share = $this->shareRepository->getShareById((int) $shareId);

        if (! $share) {
            return false;
        }

        $share->incrementViewCount();
        $this->shareRepository->save($share);

        return true;
    }

    public function getShareList(int $page, int $pageSize, array $conditions = [], string $select = '*'): array
    {
        // Define list of fields to return
        $allowedFields = [
            'id', 'resource_id', 'resource_name', 'resource_type',
            'created_at', 'created_uid', 'share_type',
        ];

        $result = $this->shareRepository->paginate($conditions, $page, $pageSize);
        // Filter fields
        $filteredList = [];
        foreach ($result['list'] as $item) {
            $filteredItem = [];
            // Convert entity to array
            $itemArray = $item instanceof ResourceShareEntity ? $item->toArray() : (array) $item;
            // Keep only allowed fields
            foreach ($allowedFields as $field) {
                if (isset($itemArray[$field])) {
                    $filteredItem[$field] = $itemArray[$field];
                }
            }

            $filteredList[] = $filteredItem;
        }
        return ['total' => $result['total'], 'list' => $filteredList];
    }

    /**
     * Save share (create or update).
     *
     * @param string $resourceId Resource ID
     * @param int $resourceType Resource type
     * @param string $userId User ID
     * @param string $organizationCode Organization code
     * @param array $attributes Extra attributes
     * @param null|string $password Password (optional)
     * @param null|int $expireDays Expiration time (optional)
     * @return ResourceShareEntity Saved share entity
     * @throws Exception If operation fails
     */
    public function saveShare(
        string $resourceId,
        int $resourceType,
        string $userId,
        string $organizationCode,
        array $attributes = [],
        ?string $password = null,
        ?int $expireDays = null
    ): ResourceShareEntity {
        // 1. Check if share already exists
        $shareEntity = $this->findExistingShare($resourceId, $resourceType, '');

        // 2. If not exists, create new share entity
        if (! $shareEntity) {
            // Generate share code - priority to passed share_code, use resource_id as fallback
            $shareCode = $attributes['share_code'] ?? $resourceId;

            // Build basic share data
            $shareData = [
                'resource_id' => $resourceId,
                'resource_type' => $resourceType,
                'resource_name' => $attributes['resource_name'],
                'share_code' => $shareCode,
                'share_type' => $attributes['share_type'] ?? 0,
                'created_uid' => $userId,
                'organization_code' => $organizationCode,
            ];

            // Create new entity
            $shareEntity = new ResourceShareEntity($shareData);

            // Set creation time
            $shareEntity->setCreatedAt(date('Y-m-d H:i:s'));
        }

        // 3. Update entity properties (whether new or existing)
        // Update share type (if provided)
        if (isset($attributes['share_type'])) {
            $shareEntity->setShareType($attributes['share_type']);
        }

        // Update extra attributes (if provided)
        if (isset($attributes['extra'])) {
            $shareEntity->setExtra($attributes['extra']);
        }

        // Set password (if provided)
        if (! empty($password)) {
            // Use reversible encryption instead of one-way hashing
            $shareEntity->setPassword(PasswordCrypt::encrypt($password));
        } else {
            $shareEntity->setPassword('');
        }
        $shareEntity->setIsPasswordEnabled((bool) $shareEntity->getPassword());

        // Set expiration time (if provided)
        if ($expireDays > 0) {
            // Ensure expiration time is in string format
            $expireAt = date('Y-m-d H:i:s', strtotime("+{$expireDays} days"));
            $shareEntity->setExpireAt($expireAt);
        }

        // Set update information
        $shareEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $shareEntity->setUpdatedUid($userId);
        $shareEntity->setDeletedAt(null);

        // 4. Save entity
        try {
            return $this->shareRepository->save($shareEntity);
        } catch (Exception $e) {
            // Re-throw exception
            ExceptionBuilder::throw(
                ShareErrorCode::OPERATION_FAILED,
                'share.save_failed',
                [$shareEntity->getId() ?: '(new)']
            );
        }
    }

    /**
     * Generate share code.
     *
     * @return string Generated share code (12 random characters)
     */
    public function generateShareCode(): string
    {
        return (new ShareCodeGenerator())
            ->setCodeLength(12) // Set to 12 digits
            ->generate();
    }

    /**
     * Regenerate share code by ID.
     *
     * @param int $shareId Share ID
     * @throws Exception If operation fails
     */
    public function regenerateShareCodeById(int $shareId): ResourceShareEntity
    {
        // 1. Get share entity
        $shareEntity = $this->shareRepository->getShareById($shareId);
        if (! $shareEntity) {
            ExceptionBuilder::throw(ShareErrorCode::NOT_FOUND);
        }

        // 3. Regenerate share code
        $newShareCode = $this->generateShareCode();
        $shareEntity->setShareCode($newShareCode);
        $shareEntity->setUpdatedAt(date('Y-m-d H:i:s'));

        // 4. Save updates
        try {
            $this->shareRepository->save($shareEntity);
            return $shareEntity;
        } catch (Exception $e) {
            ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED);
        }
    }

    /**
     * Change password.
     *
     * @param int $shareId Share ID
     * @throws Exception If operation fails
     */
    public function changePasswordById(int $shareId, string $password): ResourceShareEntity
    {
        // 1. Get share entity
        $shareEntity = $this->shareRepository->getShareById($shareId);
        if (! $shareEntity) {
            ExceptionBuilder::throw(ShareErrorCode::NOT_FOUND);
        }

        // 3. Set password
        if (! empty($password)) {
            // Use reversible encryption instead of one-way hashing
            $shareEntity->setPassword(PasswordCrypt::encrypt($password));
        } else {
            $shareEntity->setPassword('');
        }
        $shareEntity->setIsPasswordEnabled((bool) $shareEntity->getPassword());
        $shareEntity->setUpdatedAt(date('Y-m-d H:i:s'));

        // 4. Save updates
        try {
            $this->shareRepository->save($shareEntity);
            return $shareEntity;
        } catch (Exception $e) {
            ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED);
        }
    }

    /**
     * Get decrypted share password
     *
     * @param ResourceShareEntity $shareEntity Share entity
     * @return string Decrypted password
     */
    public function getDecryptedPassword(ResourceShareEntity $shareEntity): string
    {
        $encryptedPassword = $shareEntity->getPassword();
        if (empty($encryptedPassword)) {
            return '';
        }

        return PasswordCrypt::decrypt($encryptedPassword);
    }

    /**
     * Verify if share password is correct.
     *
     * @param ResourceShareEntity $shareEntity Share entity
     * @param string $password Password to verify
     * @return bool Whether password is correct
     */
    public function verifyPassword(ResourceShareEntity $shareEntity, string $password): bool
    {
        if (empty($shareEntity->getPassword())) {
            return true; // No password share, pass verification directly
        }

        $decryptedPassword = $this->getDecryptedPassword($shareEntity);
        return $decryptedPassword === $password;
    }

    /**
     * Toggle share status (enable/disable).
     *
     * @param int $shareId Share ID
     * @param bool $enabled Whether to enable
     * @param string $userId Operating user ID
     * @return ResourceShareEntity Updated share entity
     * @throws Exception If operation fails
     */
    public function toggleShareStatus(int $shareId, bool $enabled, string $userId): ResourceShareEntity
    {
        // 1. Get share entity
        $shareEntity = $this->shareRepository->getShareById($shareId);
        if (! $shareEntity) {
            ExceptionBuilder::throw(ShareErrorCode::NOT_FOUND, 'share.not_found', [$shareId]);
        }

        // 2. Permission check (only creator can operate)
        if ($shareEntity->getCreatedUid() !== $userId) {
            ExceptionBuilder::throw(ShareErrorCode::PERMISSION_DENIED, 'share.no_permission', [$shareId]);
        }

        // 3. Update enable status
        $shareEntity->setIsEnabled($enabled);
        $shareEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $shareEntity->setUpdatedUid($userId);

        // 4. Save and return
        try {
            return $this->shareRepository->save($shareEntity);
        } catch (Exception $e) {
            ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED, 'share.toggle_status_failed', [$shareId]);
        }
    }

    /**
     * Get share for specified resource.
     *
     * @param string $resourceId Resource ID
     * @param int $resourceType Resource type
     * @return null|ResourceShareEntity Share entity
     */
    public function getShareByResource(string $resourceId, int $resourceType): ?ResourceShareEntity
    {
        return $this->shareRepository->getShareByResource('', $resourceId, $resourceType, false);
    }

    /**
     * Delete share for specified resource.
     *
     * @param string $resourceId Resource ID
     * @param int $resourceType Resource type
     * @param string $userId User ID (optional, for permission check)
     * @param bool $forceDelete Whether to force delete (physical delete), default false for soft delete
     * @return bool Whether delete was successful
     */
    public function deleteShareByResource(string $resourceId, int $resourceType, string $userId = '', bool $forceDelete = false): bool
    {
        $shareEntity = $this->shareRepository->getShareByResource($userId, $resourceId, $resourceType);
        if (! $shareEntity) {
            return true; // If not exists, consider delete as successful
        }

        return $this->shareRepository->delete($shareEntity->getId(), $forceDelete);
    }

    /**
     * Delete share for specified share code.
     *
     * @param string $shareCode Share code
     * @return bool Whether delete was successful
     */
    public function deleteShareByCode(string $shareCode): bool
    {
        $shareEntity = $this->shareRepository->getShareByCode($shareCode);
        if (! $shareEntity) {
            return true; // If not exists, consider delete as successful
        }

        return $this->shareRepository->delete($shareEntity->getId());
    }

    /**
     * Batch delete shares for specified resource type.
     *
     * @param string $resourceId Resource ID
     * @param int $resourceType Resource type
     * @return bool Whether delete was successful
     */
    public function deleteAllSharesByResource(string $resourceId, int $resourceType): bool
    {
        try {
            // Can extend to batch delete here, currently use single delete
            $shareEntity = $this->shareRepository->getShareByResource('', $resourceId, $resourceType);
            if (! $shareEntity) {
                return true;
            }
            return $this->shareRepository->delete($shareEntity->getId());
        } catch (Exception $e) {
            ExceptionBuilder::throw(ShareErrorCode::OPERATION_FAILED, 'share.delete_failed: ' . $resourceId);
        }
    }

    /**
     * Find existing share.
     *
     * @param string $resourceId Resource ID
     * @param int $resourceType Resource type
     * @param string $userId User ID
     * @return null|ResourceShareEntity Return share entity if exists, otherwise null
     */
    protected function findExistingShare(string $resourceId, int $resourceType, string $userId = ''): ?ResourceShareEntity
    {
        return $this->shareRepository->getShareByResource($userId, $resourceId, $resourceType);
    }
}
