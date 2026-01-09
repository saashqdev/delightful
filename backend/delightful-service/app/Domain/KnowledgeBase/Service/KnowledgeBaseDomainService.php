<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Service;

use App\Domain\Flow\Entity\ValueObject\Code;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseFragmentEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile\Interfaces\DocumentFileInterface;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeBaseDataIsolation;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeSyncStatus;
use App\Domain\KnowledgeBase\Entity\ValueObject\Query\KnowledgeBaseFragmentQuery;
use App\Domain\KnowledgeBase\Entity\ValueObject\Query\KnowledgeBaseQuery;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseRemovedEvent;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseSavedEvent;
use App\Domain\KnowledgeBase\Repository\Facade\KnowledgeBaseFragmentRepositoryInterface;
use App\Domain\KnowledgeBase\Repository\Facade\KnowledgeBaseRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Delightful\AsyncEvent\AsyncEventUtil;
use Hyperf\DbConnection\Annotation\Transactional;
use Psr\SimpleCache\CacheInterface;

readonly class KnowledgeBaseDomainService
{
    public function __construct(
        private KnowledgeBaseRepositoryInterface $delightfulFlowKnowledgeRepository,
        private KnowledgeBaseFragmentRepositoryInterface $delightfulFlowKnowledgeFragmentRepository,
        private CacheInterface $cache,
    ) {
    }

    /**
     * saveknowledge base - basicinformation.
     * @param array<DocumentFileInterface> $files
     */
    public function save(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $savingDelightfulFlowKnowledgeEntity, array $files = []): KnowledgeBaseEntity
    {
        $savingDelightfulFlowKnowledgeEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $savingDelightfulFlowKnowledgeEntity->setCreator($dataIsolation->getCurrentUserId());
        $create = false;
        if ($savingDelightfulFlowKnowledgeEntity->shouldCreate()) {
            $savingDelightfulFlowKnowledgeEntity->prepareForCreation();
            $delightfulFlowKnowledgeEntity = $savingDelightfulFlowKnowledgeEntity;
            $create = true;

            // usealreadyalready提frontgenerategood code
            if (! empty($delightfulFlowKnowledgeEntity->getBusinessId())) {
                $tempCode = $this->getTempCodeByBusinessId($delightfulFlowKnowledgeEntity->getType(), $delightfulFlowKnowledgeEntity->getBusinessId());
                if (! empty($tempCode)) {
                    $delightfulFlowKnowledgeEntity->setCode($tempCode);
                }
            }
        } else {
            $delightfulFlowKnowledgeEntity = $this->delightfulFlowKnowledgeRepository->getByCode($dataIsolation, $savingDelightfulFlowKnowledgeEntity->getCode());
            if (empty($delightfulFlowKnowledgeEntity)) {
                ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'flow.common.not_found', ['label' => $savingDelightfulFlowKnowledgeEntity->getCode()]);
            }
            $savingDelightfulFlowKnowledgeEntity->prepareForModification($delightfulFlowKnowledgeEntity);
        }

        $delightfulFlowKnowledgeEntity = $this->delightfulFlowKnowledgeRepository->save($dataIsolation, $delightfulFlowKnowledgeEntity);

        $event = new KnowledgeBaseSavedEvent($dataIsolation, $delightfulFlowKnowledgeEntity, $create, $files);
        AsyncEventUtil::dispatch($event);

        return $delightfulFlowKnowledgeEntity;
    }

    /**
     * saveknowledge base - toquantityenterdegree.
     */
    public function saveProcess(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $savingKnowledgeEntity): KnowledgeBaseEntity
    {
        $knowledgeEntity = $this->delightfulFlowKnowledgeRepository->getByCode($dataIsolation, $savingKnowledgeEntity->getCode());
        if (empty($knowledgeEntity)) {
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'common.not_found', ['label' => $savingKnowledgeEntity->getCode()]);
        }
        $savingKnowledgeEntity->prepareForModifyProcess($knowledgeEntity);
        return $this->delightfulFlowKnowledgeRepository->save($dataIsolation, $knowledgeEntity);
    }

    /**
     * queryknowledge basecolumntable.
     * @return array{total: int, list: array<KnowledgeBaseEntity>}
     */
    public function queries(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseQuery $query, Page $page): array
    {
        return $this->delightfulFlowKnowledgeRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * @return array<KnowledgeBaseEntity>
     */
    public function getByCodes(KnowledgeBaseDataIsolation $dataIsolation, array $codes): array
    {
        // minutebatchquery
        $chunks = array_chunk($codes, 500);
        $entities = [];
        foreach ($chunks as $chunk) {
            $entities = array_merge($entities, $this->delightfulFlowKnowledgeRepository->getByCodes($dataIsolation, $chunk));
        }
        return $entities;
    }

    /**
     * queryoneknowledge base.
     */
    public function show(KnowledgeBaseDataIsolation $dataIsolation, string $code, bool $checkCollection = false): KnowledgeBaseEntity
    {
        $delightfulFlowKnowledgeEntity = $this->delightfulFlowKnowledgeRepository->getByCode($dataIsolation, $code);
        if (empty($delightfulFlowKnowledgeEntity)) {
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'flow.common.not_found', ['label' => $code]);
        }
        if ($checkCollection) {
            $collection = $delightfulFlowKnowledgeEntity->getVectorDBDriver()->getCollection($delightfulFlowKnowledgeEntity->getCollectionName());
            if ($collection) {
                $delightfulFlowKnowledgeEntity->setCompletedCount($collection->pointsCount);
            }
            $query = new KnowledgeBaseFragmentQuery();
            $query->setKnowledgeCode($delightfulFlowKnowledgeEntity->getCode());
            $delightfulFlowKnowledgeEntity->setFragmentCount($this->delightfulFlowKnowledgeFragmentRepository->count($dataIsolation, $query));

            $query->setSyncStatus(KnowledgeSyncStatus::Synced->value);
            $delightfulFlowKnowledgeEntity->setExpectedCount($this->delightfulFlowKnowledgeFragmentRepository->count($dataIsolation, $query));
        }

        return $delightfulFlowKnowledgeEntity;
    }

    /**
     * knowledge basewhether存in.
     */
    public function exist(KnowledgeBaseDataIsolation $dataIsolation, string $code): bool
    {
        return $this->delightfulFlowKnowledgeRepository->exist($dataIsolation, $code);
    }

    /**
     * deleteknowledge base.
     */
    #[Transactional]
    public function destroy(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $delightfulFlowKnowledgeEntity): void
    {
        $this->delightfulFlowKnowledgeRepository->destroy($dataIsolation, $delightfulFlowKnowledgeEntity);
        $this->delightfulFlowKnowledgeFragmentRepository->destroyByKnowledgeCode($dataIsolation, $delightfulFlowKnowledgeEntity->getCode());
        AsyncEventUtil::dispatch(new KnowledgeBaseRemovedEvent($dataIsolation, $delightfulFlowKnowledgeEntity));
    }

    /**
     * updateknowledge basestatus
     */
    public function changeSyncStatus(KnowledgeBaseEntity|KnowledgeBaseFragmentEntity $entity): void
    {
        if ($entity instanceof KnowledgeBaseEntity) {
            $this->delightfulFlowKnowledgeRepository->changeSyncStatus($entity);
        }
        if ($entity instanceof KnowledgeBaseFragmentEntity) {
            $this->delightfulFlowKnowledgeFragmentRepository->changeSyncStatus($entity);
        }
    }

    public function updateKnowledgeBaseWordCount(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeCode, int $deltaWordCount): void
    {
        if ($deltaWordCount === 0) {
            return;
        }
        $this->delightfulFlowKnowledgeRepository->updateWordCount($dataIsolation, $knowledgeCode, $deltaWordCount);
    }

    public function generateTempCodeByBusinessId(int $knowledgeType, string $businessId): string
    {
        $key = 'knowledge-code:generate:' . $knowledgeType . ':' . $businessId;
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }
        $code = Code::Knowledge->gen();
        $this->cache->set($key, $code, 7 * 24 * 60 * 60);
        return $code;
    }

    public function getTempCodeByBusinessId(int $knowledgeType, string $businessId): string
    {
        $key = 'knowledge-code:generate:' . $knowledgeType . ':' . $businessId;
        $value = $this->cache->get($key, '');
        $this->cache->delete($key);
        return $value;
    }
}
