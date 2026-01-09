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
     * get模typelist (管理back台use，contain完整i18nfield).
     */
    public function getModes(DelightfulUserAuthorization $authorization, Page $page): array
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);
        // 管理back台query：sort降序，notfilterdefault模type
        $query = new ModeQuery('desc', false);
        $result = $this->modeDomainService->getModes($dataIsolation, $query, $page);

        return [
            'total' => $result['total'],
            'list' => AdminModeAssembler::entitiesToAdminDTOs($result['list']),
        ];
    }

    /**
     * according toIDget模type聚合root（contain模typedetail、minutegroup、model关系）.
     */
    public function getModeById(DelightfulUserAuthorization $authorization, string $id): AdminModeAggregateDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);
        $modeAggregate = $this->modeDomainService->getModeDetailById($dataIsolation, $id);

        if (! $modeAggregate) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_NOT_FOUND);
        }

        $providerModels = $this->getDetailedModels($modeAggregate);

        // convert为DTO
        $modeAggregateDTO = AdminModeAssembler::aggregateToAdminDTO($modeAggregate, $providerModels);

        // processicon
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
        // convert为DTO
        $modeAggregateDTO = AdminModeAssembler::aggregateToAdminDTO($modeAggregate, $providerModels);

        // processicon
        $this->processModeAggregateIcons($modeAggregateDTO);

        return $modeAggregateDTO;
    }

    /**
     * create模type (管理back台use).
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
     * update模type.
     */
    public function updateMode(DelightfulUserAuthorization $authorization, string $modeId, UpdateModeRequest $request): AdminModeAggregateDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);

        // 先get现have的完整实body
        $existingMode = $this->modeDomainService->getModeById($dataIsolation, $modeId);
        if (! $existingMode) {
            ExceptionBuilder::throw(ModeErrorCode::MODE_NOT_FOUND);
        }

        Db::beginTransaction();
        try {
            // 将updaterequestapplicationto现have实body（只updateallow修改的field）
            AdminModeAssembler::applyUpdateRequestToEntity($request, $existingMode);

            $updatedMode = $this->modeDomainService->updateMode($dataIsolation, $existingMode);

            Db::commit();

            // 重新get聚合rootinfo
            $updatedModeAggregate = $this->modeDomainService->getModeDetailById($dataIsolation, $updatedMode->getId());
            return AdminModeAssembler::aggregateToAdminDTO($updatedModeAggregate);
        } catch (Exception $exception) {
            $this->logger->warning('Update mode failed: ' . $exception->getMessage());
            Db::rollBack();
            throw $exception;
        }
    }

    /**
     * update模typestatus
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
     * getdefault模type.
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
     * save模typeconfiguration.
     */
    public function saveModeConfig(DelightfulUserAuthorization $authorization, AdminModeAggregateDTO $modeAggregateDTO): AdminModeAggregateDTO
    {
        $dataIsolation = $this->getModeDataIsolation($authorization);

        Db::beginTransaction();
        try {
            // 将DTOconvert为领域object
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
