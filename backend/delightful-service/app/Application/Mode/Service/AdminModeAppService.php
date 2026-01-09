<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Mode\Service;

use App\Application\Mode\Assembler\AdminModeAssembler;
use App\Application\Mode\DTO\Admin\AdminModeAggregateDTO;
use App\Application\Mode\DTO\Admin\AdminModeDTO;
use App\Domain\Mode\Entity\ValueQuery\ModeQuery;
use App\ErrorCode\ModeErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Mode\DTO\Request\CreateModeRequest;
use App\Interfaces\Mode\DTO\Request\UpdateModeRequest;
use Exception;
use Hyperf\DbConnection\Db;

class AdminModeAppService extends AbstractModeAppService
{
    /**
     * get模式list (管理后台用，包含完整i18nfield).
     */
    public function getModes(DelightfulUserAuthorization $authorization, Page $page): array
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);
        // 管理后台query：sort降序，不filter默认模式
        $query = new ModeQuery('desc', false);
        $result = $this->modeDomainService->getModes($dataIsolation, $query, $page);

        return [
            'total' => $result['total'],
            'list' => AdminModeAssembler::entitiesToAdminDTOs($result['list']),
        ];
    }

    /**
     * according toIDget模式聚合根（包含模式详情、分组、model关系）.
     */
    public function getModeById(DelightfulUserAuthorization $authorization, string $id): AdminModeAggregateDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);
        $modeAggregate = $this->modeDomainService->getModeDetailById($dataIsolation, $id);

        if (! $modeAggregate) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_NOT_FOUND);
        }

        $providerModels = $this->getDetailedModels($modeAggregate);

        // 转换为DTO
        $modeAggregateDTO = AdminModeAssembler::aggregateToAdminDTO($modeAggregate, $providerModels);

        // 处理icon
        $this->processModeAggregateIcons($modeAggregateDTO);

        return $modeAggregateDTO;
    }

    public function getOriginMode(DelightfulUserAuthorization $authorization, string $id): AdminModeAggregateDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);
        $modeAggregate = $this->modeDomainService->getOriginMode($dataIsolation, $id);
        if (! $modeAggregate) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_NOT_FOUND);
        }
        $providerModels = $this->getDetailedModels($modeAggregate);
        // 转换为DTO
        $modeAggregateDTO = AdminModeAssembler::aggregateToAdminDTO($modeAggregate, $providerModels);

        // 处理icon
        $this->processModeAggregateIcons($modeAggregateDTO);

        return $modeAggregateDTO;
    }

    /**
     * create模式 (管理后台用).
     */
    public function createMode(DelightfulUserAuthorization $authorization, CreateModeRequest $request): AdminModeDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);

        Db::beginTransaction();
        try {
            $modeEntity = AdminModeAssembler::createModeRequestToEntity(
                $request
            );
            $savedMode = $this->modeDomainService->createMode($dataIsolation, $modeEntity);

            Db::commit();

            $modeEntity = $this->modeDomainService->getModeById($dataIsolation, $savedMode->getId());
            return AdminModeAssembler::modeToAdminDTO($modeEntity);
        } catch (Exception $exception) {
            $this->logger->warning('Create mode failed: ' . $exception->getMessage());
            Db::rollBack();
            throw $exception;
        }
    }

    /**
     * update模式.
     */
    public function updateMode(DelightfulUserAuthorization $authorization, string $modeId, UpdateModeRequest $request): AdminModeAggregateDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);

        // 先get现有的完整实体
        $existingMode = $this->modeDomainService->getModeById($dataIsolation, $modeId);
        if (! $existingMode) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_NOT_FOUND);
        }

        Db::beginTransaction();
        try {
            // 将update请求应用到现有实体（只update允许修改的field）
            AdminModeAssembler::applyUpdateRequestToEntity($request, $existingMode);

            $updatedMode = $this->modeDomainService->updateMode($dataIsolation, $existingMode);

            Db::commit();

            // 重新get聚合根info
            $updatedModeAggregate = $this->modeDomainService->getModeDetailById($dataIsolation, $updatedMode->getId());
            return AdminModeAssembler::aggregateToAdminDTO($updatedModeAggregate);
        } catch (Exception $exception) {
            $this->logger->warning('Update mode failed: ' . $exception->getMessage());
            Db::rollBack();
            throw $exception;
        }
    }

    /**
     * update模式status
     */
    public function updateModeStatus(DelightfulUserAuthorization $authorization, string $id, bool $status): bool
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);

        try {
            return $this->modeDomainService->updateModeStatus($dataIsolation, $id, $status);
        } catch (Exception $exception) {
            $this->logger->warning('Update mode status failed: ' . $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * get默认模式.
     */
    public function getDefaultMode(DelightfulUserAuthorization $authorization): ?AdminModeAggregateDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);
        $defaultModeAggregate = $this->modeDomainService->getDefaultMode($dataIsolation);
        $providerModels = $this->getDetailedModels($defaultModeAggregate);

        $adminModeAggregateDTO = AdminModeAssembler::aggregateToAdminDTO($defaultModeAggregate, $providerModels);

        $this->processModeAggregateIcons($adminModeAggregateDTO);

        return $adminModeAggregateDTO;
    }

    /**
     * 保存模式configuration.
     */
    public function saveModeConfig(DelightfulUserAuthorization $authorization, AdminModeAggregateDTO $modeAggregateDTO): AdminModeAggregateDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);

        Db::beginTransaction();
        try {
            // 将DTO转换为领域object
            $modeAggregateEntity = AdminModeAssembler::aggregateDTOToEntity($modeAggregateDTO);

            $this->modeDomainService->saveModeConfig($dataIsolation, $modeAggregateEntity);

            Db::commit();

            return $this->getModeById($authorization, $modeAggregateDTO->getMode()->getId());
        } catch (Exception $exception) {
            $this->logger->warning('Save mode config failed: ' . $exception->getMessage());
            Db::rollBack();
            throw $exception;
        }
    }
}
