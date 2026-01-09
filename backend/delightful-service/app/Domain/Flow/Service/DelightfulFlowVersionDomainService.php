<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\DelightfulFlowVersionEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowVersionQuery;
use App\Domain\Flow\Event\DelightfulFlowPublishedEvent;
use App\Domain\Flow\Repository\Facade\DelightfulFlowRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowVersionRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Delightful\AsyncEvent\AsyncEventUtil;
use Hyperf\DbConnection\Annotation\Transactional;

class DelightfulFlowVersionDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowRepositoryInterface $delightfulFlowRepository,
        private readonly DelightfulFlowVersionRepositoryInterface $delightfulFlowVersionRepository,
    ) {
    }

    /**
     * @return array<DelightfulFlowVersionEntity>
     */
    public function getByCodes(FlowDataIsolation $dataIsolation, array $versionCodes): array
    {
        return $this->delightfulFlowVersionRepository->getByCodes($dataIsolation, $versionCodes);
    }

    public function getLastVersion(FlowDataIsolation $dataIsolation, string $flowCode): ?DelightfulFlowVersionEntity
    {
        return $this->delightfulFlowVersionRepository->getLastVersion($dataIsolation, $flowCode);
    }

    /**
     * queryversioncolumntable.
     * @return array{total: int, list: array<DelightfulFlowVersionEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowVersionQuery $query, Page $page): array
    {
        return $this->delightfulFlowVersionRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * getversiondetail.
     */
    public function show(FlowDataIsolation $dataIsolation, string $flowCode, string $versionCode): DelightfulFlowVersionEntity
    {
        $version = $this->delightfulFlowVersionRepository->getByFlowCodeAndCode($dataIsolation, $flowCode, $versionCode);
        if (! $version) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$versionCode} notexistsin");
        }
        return $version;
    }

    /**
     * hairversion.
     */
    #[Transactional]
    public function publish(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $delightfulFlow, DelightfulFlowVersionEntity $delightfulFlowVersionEntity): DelightfulFlowVersionEntity
    {
        $delightfulFlowVersionEntity->setCreator($dataIsolation->getCurrentUserId());
        $delightfulFlowVersionEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $delightfulFlowVersionEntity->prepareForCreation();
        if (empty($delightfulFlow->getVersionCode())) {
            $delightfulFlow->setEnabled(true);
            $delightfulFlowVersionEntity->getDelightfulFlow()->setEnabled(true);
        }
        $delightfulFlow->prepareForPublish($delightfulFlowVersionEntity, $dataIsolation->getCurrentUserId());

        $delightfulFlowVersionEntity = $this->delightfulFlowVersionRepository->create($dataIsolation, $delightfulFlowVersionEntity);
        $this->delightfulFlowRepository->save($dataIsolation, $delightfulFlow);
        AsyncEventUtil::dispatch(new DelightfulFlowPublishedEvent($delightfulFlowVersionEntity->getDelightfulFlow()));
        return $delightfulFlowVersionEntity;
    }

    /**
     * rollbackversion.
     */
    public function rollback(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $delightfulFlow, string $versionCode): DelightfulFlowVersionEntity
    {
        $version = $this->delightfulFlowVersionRepository->getByFlowCodeAndCode($dataIsolation, $delightfulFlow->getCode(), $versionCode);
        if (! $version) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$versionCode} notexistsin");
        }

        $delightfulFlow->prepareForPublish($version, $dataIsolation->getCurrentUserId());
        $this->delightfulFlowRepository->save($dataIsolation, $delightfulFlow);
        AsyncEventUtil::dispatch(new DelightfulFlowPublishedEvent($delightfulFlow));
        return $version;
    }

    public function existVersion(FlowDataIsolation $dataIsolation, string $flowCode): bool
    {
        return $this->delightfulFlowVersionRepository->existVersion($dataIsolation, $flowCode);
    }
}
