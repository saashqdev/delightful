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
        private readonly DelightfulFlowRepositoryInterface $magicFlowRepository,
        private readonly DelightfulFlowVersionRepositoryInterface $magicFlowVersionRepository,
    ) {
    }

    /**
     * @return array<DelightfulFlowVersionEntity>
     */
    public function getByCodes(FlowDataIsolation $dataIsolation, array $versionCodes): array
    {
        return $this->magicFlowVersionRepository->getByCodes($dataIsolation, $versionCodes);
    }

    public function getLastVersion(FlowDataIsolation $dataIsolation, string $flowCode): ?DelightfulFlowVersionEntity
    {
        return $this->magicFlowVersionRepository->getLastVersion($dataIsolation, $flowCode);
    }

    /**
     * 查询版本列表.
     * @return array{total: int, list: array<DelightfulFlowVersionEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowVersionQuery $query, Page $page): array
    {
        return $this->magicFlowVersionRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * 获取版本详情.
     */
    public function show(FlowDataIsolation $dataIsolation, string $flowCode, string $versionCode): DelightfulFlowVersionEntity
    {
        $version = $this->magicFlowVersionRepository->getByFlowCodeAndCode($dataIsolation, $flowCode, $versionCode);
        if (! $version) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$versionCode} 不存在");
        }
        return $version;
    }

    /**
     * 发版.
     */
    #[Transactional]
    public function publish(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $magicFlow, DelightfulFlowVersionEntity $magicFlowVersionEntity): DelightfulFlowVersionEntity
    {
        $magicFlowVersionEntity->setCreator($dataIsolation->getCurrentUserId());
        $magicFlowVersionEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $magicFlowVersionEntity->prepareForCreation();
        if (empty($magicFlow->getVersionCode())) {
            $magicFlow->setEnabled(true);
            $magicFlowVersionEntity->getDelightfulFlow()->setEnabled(true);
        }
        $magicFlow->prepareForPublish($magicFlowVersionEntity, $dataIsolation->getCurrentUserId());

        $magicFlowVersionEntity = $this->magicFlowVersionRepository->create($dataIsolation, $magicFlowVersionEntity);
        $this->magicFlowRepository->save($dataIsolation, $magicFlow);
        AsyncEventUtil::dispatch(new DelightfulFlowPublishedEvent($magicFlowVersionEntity->getDelightfulFlow()));
        return $magicFlowVersionEntity;
    }

    /**
     * 回滚版本.
     */
    public function rollback(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $magicFlow, string $versionCode): DelightfulFlowVersionEntity
    {
        $version = $this->magicFlowVersionRepository->getByFlowCodeAndCode($dataIsolation, $magicFlow->getCode(), $versionCode);
        if (! $version) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$versionCode} 不存在");
        }

        $magicFlow->prepareForPublish($version, $dataIsolation->getCurrentUserId());
        $this->magicFlowRepository->save($dataIsolation, $magicFlow);
        AsyncEventUtil::dispatch(new DelightfulFlowPublishedEvent($magicFlow));
        return $version;
    }

    public function existVersion(FlowDataIsolation $dataIsolation, string $flowCode): bool
    {
        return $this->magicFlowVersionRepository->existVersion($dataIsolation, $flowCode);
    }
}
