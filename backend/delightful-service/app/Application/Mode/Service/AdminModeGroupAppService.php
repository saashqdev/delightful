<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Mode\Service;

use App\Application\Mode\Assembler\AdminModeAssembler;
use App\Application\Mode\DTO\Admin\AdminModeGroupDTO;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Mode\DTO\Request\CreateModeGroupRequest;
use App\Interfaces\Mode\DTO\Request\UpdateModeGroupRequest;
use Exception;
use Hyperf\DbConnection\Db;
use InvalidArgumentException;

class AdminModeGroupAppService extends AbstractModeAppService
{
    /**
     * according tomodeIDget分组列表 (管理后台用，contain完整i18n字段).
     */
    public function getGroupsByModeId(DelightfulUserAuthorization $authorization, string $modeId): array
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);
        $groups = $this->groupDomainService->getGroupsByModeId($dataIsolation, $modeId);

        $groupDTOs = AdminModeAssembler::groupEntitiesToAdminDTOs($groups);

        // process分组图标
        $this->processGroupIcons($groupDTOs);

        return $groupDTOs;
    }

    /**
     * get分组detail (管理后台用).
     */
    public function getGroupById(DelightfulUserAuthorization $authorization, string $groupId): ?array
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);
        $group = $this->groupDomainService->getGroupById($dataIsolation, $groupId);

        if (! $group) {
            return null;
        }

        $models = $this->groupDomainService->getGroupModels($dataIsolation, $groupId);
        $groupDTO = AdminModeAssembler::groupEntityToAdminDTO($group);
        $relationDTOs = AdminModeAssembler::relationEntitiesToDTOs($models);

        return [
            'group' => $groupDTO->toArray(),
            'models' => $relationDTOs,
        ];
    }

    /**
     * create分组 (管理后台用).
     */
    public function createGroup(DelightfulUserAuthorization $authorization, CreateModeGroupRequest $request): AdminModeGroupDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);

        Db::beginTransaction();
        try {
            $groupEntity = AdminModeAssembler::createModeGroupRequestToEntity(
                $request
            );

            $savedGroup = $this->groupDomainService->createGroup($dataIsolation, $groupEntity);

            Db::commit();

            $adminModeGroupDTO = AdminModeAssembler::groupEntityToAdminDTO($savedGroup);

            $fileLinks = $this->fileDomainService->getBatchLinksByOrgPaths([$adminModeGroupDTO->getIcon()]);
            if (isset($fileLinks[$adminModeGroupDTO->getIcon()])) {
                $adminModeGroupDTO->setIcon($fileLinks[$adminModeGroupDTO->getIcon()]->getUrl());
            }
            return $adminModeGroupDTO;
        } catch (Exception $exception) {
            $this->logger->warning('Create mode group failed: ' . $exception->getMessage());
            Db::rollBack();
            throw $exception;
        }
    }

    /**
     * update分组 (管理后台用).
     */
    public function updateGroup(DelightfulUserAuthorization $authorization, string $groupId, UpdateModeGroupRequest $request): AdminModeGroupDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);

        Db::beginTransaction();
        try {
            // 从requestobject直接转换为实体
            $groupEntity = AdminModeAssembler::updateModeGroupRequestToEntity($request, $groupId);

            $updatedGroup = $this->groupDomainService->updateGroup($dataIsolation, $groupEntity);

            Db::commit();

            $adminModeGroupDTO = AdminModeAssembler::groupEntityToAdminDTO($updatedGroup);
            $fileLinks = $this->fileDomainService->getBatchLinksByOrgPaths([$adminModeGroupDTO->getIcon()]);
            if (isset($fileLinks[$adminModeGroupDTO->getIcon()])) {
                $adminModeGroupDTO->setIcon($fileLinks[$updatedGroup->getIcon()]->getUrl());
            }
            return $adminModeGroupDTO;
        } catch (Exception $exception) {
            $this->logger->warning('Update mode group failed: ' . $exception->getMessage());
            Db::rollBack();
            throw $exception;
        }
    }

    /**
     * delete分组.
     */
    public function deleteGroup(DelightfulUserAuthorization $authorization, string $groupId): void
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);

        Db::beginTransaction();
        try {
            $success = $this->groupDomainService->deleteGroup($dataIsolation, $groupId);
            if (! $success) {
                throw new InvalidArgumentException('Failed to delete group');
            }

            Db::commit();
        } catch (Exception $exception) {
            $this->logger->warning('Delete mode group failed: ' . $exception->getMessage());
            Db::rollBack();
            throw $exception;
        }
    }
}
