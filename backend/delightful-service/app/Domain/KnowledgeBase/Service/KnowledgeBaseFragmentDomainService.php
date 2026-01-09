<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Service;

use App\Domain\KnowledgeBase\Entity\KnowledgeBaseDocumentEntity;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseFragmentEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\FragmentConfig;
use App\Domain\KnowledgeBase\Entity\ValueObject\FragmentMode;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeBaseDataIsolation;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeSyncStatus;
use App\Domain\KnowledgeBase\Entity\ValueObject\Query\KnowledgeBaseFragmentQuery;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseFragmentRemovedEvent;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseFragmentSavedEvent;
use App\Domain\KnowledgeBase\Repository\Facade\KnowledgeBaseDocumentRepositoryInterface;
use App\Domain\KnowledgeBase\Repository\Facade\KnowledgeBaseFragmentRepositoryInterface;
use App\Domain\KnowledgeBase\Repository\Facade\KnowledgeBaseRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\Odin\TextSplitter\TokenTextSplitter;
use App\Infrastructure\Util\Text\TextPreprocess\TextPreprocessUtil;
use App\Infrastructure\Util\Text\TextPreprocess\ValueObject\TextPreprocessRule;
use App\Infrastructure\Util\Time\TimeUtil;
use Delightful\AsyncEvent\AsyncEventUtil;
use Exception;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

readonly class KnowledgeBaseFragmentDomainService
{
    private LoggerInterface $logger;

    public function __construct(
        private KnowledgeBaseFragmentRepositoryInterface $knowledgeBaseFragmentRepository,
        private KnowledgeBaseRepositoryInterface $knowledgeBaseRepository,
        private KnowledgeBaseDocumentRepositoryInterface $knowledgeBaseDocumentRepository,
        LoggerFactory $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    /**
     * @return array{total: int, list: array<KnowledgeBaseFragmentEntity>}
     */
    public function queries(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseFragmentQuery $query, Page $page): array
    {
        return $this->knowledgeBaseFragmentRepository->queries($dataIsolation, $query, $page);
    }

    public function show(KnowledgeBaseDataIsolation $dataIsolation, int $id, bool $selectForUpdate = false, bool $throw = true): ?KnowledgeBaseFragmentEntity
    {
        $delightfulFlowKnowledgeFragmentEntity = $this->knowledgeBaseFragmentRepository->getById($dataIsolation, $id, $selectForUpdate);
        if (empty($delightfulFlowKnowledgeFragmentEntity) && $throw) {
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, "[{$id}] not存in");
        }
        return $delightfulFlowKnowledgeFragmentEntity;
    }

    public function save(
        KnowledgeBaseDataIsolation $dataIsolation,
        KnowledgeBaseEntity $knowledgeBaseEntity,
        KnowledgeBaseDocumentEntity $knowledgeBaseDocumentEntity,
        KnowledgeBaseFragmentEntity $savingDelightfulFlowKnowledgeFragmentEntity
    ): KnowledgeBaseFragmentEntity {
        $savingDelightfulFlowKnowledgeFragmentEntity->setKnowledgeCode($knowledgeBaseEntity->getCode());
        $savingDelightfulFlowKnowledgeFragmentEntity->setDocumentCode($knowledgeBaseDocumentEntity->getCode());
        $savingDelightfulFlowKnowledgeFragmentEntity->setCreator($dataIsolation->getCurrentUserId());

        // ifhavebusinessid,andandbusiness ID 存in,alsocan相whenatupdate
        $knowledgeBaseFragmentEntity = null;
        if (! empty($savingDelightfulFlowKnowledgeFragmentEntity->getBusinessId()) && empty($savingDelightfulFlowKnowledgeFragmentEntity->getId())) {
            $knowledgeBaseFragmentEntity = $this->knowledgeBaseFragmentRepository->getByBusinessId($dataIsolation, $savingDelightfulFlowKnowledgeFragmentEntity->getKnowledgeCode(), $savingDelightfulFlowKnowledgeFragmentEntity->getBusinessId());
            if (! is_null($knowledgeBaseFragmentEntity)) {
                $savingDelightfulFlowKnowledgeFragmentEntity->setId($knowledgeBaseFragmentEntity->getId());
            }
        }

        if ($savingDelightfulFlowKnowledgeFragmentEntity->shouldCreate()) {
            $savingDelightfulFlowKnowledgeFragmentEntity->prepareForCreation();
            $knowledgeBaseFragmentEntity = $savingDelightfulFlowKnowledgeFragmentEntity;
        } else {
            $knowledgeBaseFragmentEntity = $knowledgeBaseFragmentEntity ?? $this->knowledgeBaseFragmentRepository->getById($dataIsolation, $savingDelightfulFlowKnowledgeFragmentEntity->getId());
            if (empty($knowledgeBaseFragmentEntity)) {
                ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, "[{$savingDelightfulFlowKnowledgeFragmentEntity->getId()}] nothave找to");
            }
            // ifnothavechange,thennotneedupdate
            if (! $knowledgeBaseFragmentEntity->hasModify($savingDelightfulFlowKnowledgeFragmentEntity)) {
                return $knowledgeBaseFragmentEntity;
            }

            $savingDelightfulFlowKnowledgeFragmentEntity->prepareForModification($knowledgeBaseFragmentEntity);
        }

        Db::transaction(function () use ($dataIsolation, $knowledgeBaseFragmentEntity) {
            $oldKnowledgeBaseFragmentEntity = $this->knowledgeBaseFragmentRepository->getById($dataIsolation, $knowledgeBaseFragmentEntity->getId() ?? 0, true);
            $knowledgeBaseFragmentEntity = $this->knowledgeBaseFragmentRepository->save($dataIsolation, $knowledgeBaseFragmentEntity);
            $deltaWordCount = $knowledgeBaseFragmentEntity->getWordCount() - $oldKnowledgeBaseFragmentEntity?->getWordCount() ?? 0;
            $this->updateWordCount($dataIsolation, $knowledgeBaseFragmentEntity, $deltaWordCount);
        });

        $event = new KnowledgeBaseFragmentSavedEvent($dataIsolation, $knowledgeBaseEntity, $knowledgeBaseFragmentEntity);
        AsyncEventUtil::dispatch($event);

        return $knowledgeBaseFragmentEntity;
    }

    /**
     * @param array<KnowledgeBaseFragmentEntity> $fragmentEntities
     * @return array<KnowledgeBaseFragmentEntity>
     */
    public function upsert(KnowledgeBaseDataIsolation $dataIsolation, array $fragmentEntities): array
    {
        $this->knowledgeBaseFragmentRepository->upsertById($dataIsolation, $fragmentEntities);
        return $fragmentEntities;
    }

    public function showByBusinessId(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeCode, string $businessId): KnowledgeBaseFragmentEntity
    {
        $delightfulFlowKnowledgeFragmentEntity = $this->knowledgeBaseFragmentRepository->getByBusinessId($dataIsolation, $knowledgeCode, $businessId);
        if (empty($delightfulFlowKnowledgeFragmentEntity)) {
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, "[{$businessId}] not存in");
        }
        return $delightfulFlowKnowledgeFragmentEntity;
    }

    public function destroy(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $knowledgeBaseEntity, KnowledgeBaseFragmentEntity $knowledgeBaseFragmentEntity): void
    {
        Db::transaction(function () use ($dataIsolation, $knowledgeBaseFragmentEntity) {
            $oldKnowledgeBaseFragmentEntity = $this->knowledgeBaseFragmentRepository->getById($dataIsolation, $knowledgeBaseFragmentEntity->getId(), true);
            $this->knowledgeBaseFragmentRepository->destroy($dataIsolation, $knowledgeBaseFragmentEntity);
            // needupdatecharacter数
            $deltaWordCount = -$oldKnowledgeBaseFragmentEntity->getWordCount();
            $this->updateWordCount($dataIsolation, $oldKnowledgeBaseFragmentEntity, $deltaWordCount);
        });

        AsyncEventUtil::dispatch(new KnowledgeBaseFragmentRemovedEvent($dataIsolation, $knowledgeBaseEntity, $knowledgeBaseFragmentEntity));
    }

    public function batchDestroyByPointIds(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $knowledgeEntity, array $pointIds): void
    {
        $this->knowledgeBaseFragmentRepository->fragmentBatchDestroyByPointIds($dataIsolation, $knowledgeEntity->getCode(), $pointIds);
    }

    /**
     * @return array<KnowledgeBaseFragmentEntity>
     */
    public function getByIds(KnowledgeBaseDataIsolation $dataIsolation, array $ids): array
    {
        return $this->knowledgeBaseFragmentRepository->getByIds($dataIsolation, $ids);
    }

    /**
     * according to point_id get所have相closeslicesegment,按 version 倒序sort.
     * @return array<KnowledgeBaseFragmentEntity>
     */
    public function getFragmentsByPointId(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeCode, string $pointId, bool $lock = false): array
    {
        return $this->knowledgeBaseFragmentRepository->getFragmentsByPointId($dataIsolation, $knowledgeCode, $pointId, $lock);
    }

    /**
     * @return array<string, KnowledgeSyncStatus>
     */
    public function getFinalSyncStatusByDocumentCodes(KnowledgeBaseDataIsolation $dataIsolation, array $documentCodes): array
    {
        return $this->knowledgeBaseFragmentRepository->getFinalSyncStatusByDocumentCodes($dataIsolation, $documentCodes);
    }

    /**
     * updateknowledge baseslicesegmentstatus.
     */
    public function batchChangeSyncStatus(array $ids, KnowledgeSyncStatus $syncStatus, string $syncMessage = ''): void
    {
        $this->knowledgeBaseFragmentRepository->batchChangeSyncStatus($ids, $syncStatus, $syncMessage);
    }

    /**
     * @return array<string>
     * @throws Exception
     */
    public function processFragmentsByContent(KnowledgeBaseDataIsolation $dataIsolation, string $content, FragmentConfig $fragmentConfig): array
    {
        $selectedFragmentConfig = match ($fragmentConfig->getMode()) {
            FragmentMode::NORMAL => $fragmentConfig->getNormal(),
            FragmentMode::PARENT_CHILD => $fragmentConfig->getParentChild(),
            default => ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed),
        };
        $preprocessRule = $selectedFragmentConfig->getTextPreprocessRule();
        // 先conduct预process
        // needfilterREPLACE_WHITESPACErule,REPLACE_WHITESPACEruleinminutesegmentbackconductprocess
        $filterPreprocessRule = array_filter($preprocessRule, fn (TextPreprocessRule $rule) => $rule !== TextPreprocessRule::REPLACE_WHITESPACE);
        $start = microtime(true);
        $this->logger->info('front置text预processstart.');
        $content = TextPreprocessUtil::preprocess($filterPreprocessRule, $content);
        $this->logger->info('front置text预processend,耗o clock:' . TimeUtil::getMillisecondDiffFromNow($start) / 1000);

        // againconductminutesegment
        // process转义minute隔符
        $start = microtime(true);
        $this->logger->info('textminutesegmentstart.');
        $separator = stripcslashes($selectedFragmentConfig->getSegmentRule()->getSeparator());
        $splitter = new TokenTextSplitter(
            chunkSize: $selectedFragmentConfig->getSegmentRule()->getChunkSize(),
            chunkOverlap: $selectedFragmentConfig->getSegmentRule()->getChunkOverlap(),
            fixedSeparator: $separator,
            preserveSeparator: true,
        );

        $fragments = $splitter->splitText($content);
        $this->logger->info('textminutesegmentend,耗o clock:' . TimeUtil::getMillisecondDiffFromNow($start) / 1000);

        // need额outsideconductprocessrule
        $start = microtime(true);
        $this->logger->info('back置text预processstart.');
        if (in_array(TextPreprocessRule::REPLACE_WHITESPACE, $preprocessRule)) {
            foreach ($fragments as &$fragment) {
                $fragment = TextPreprocessUtil::preprocess([TextPreprocessRule::REPLACE_WHITESPACE], $fragment);
            }
        }
        $this->logger->info('back置text预processend,耗o clock:' . TimeUtil::getMillisecondDiffFromNow($start) / 1000);

        // filter掉emptystring
        return array_values(array_filter($fragments, function ($fragment) {
            return trim($fragment) !== '';
        }));
    }

    /**
     * @return array<KnowledgeBaseFragmentEntity>
     */
    public function getFragmentsWithEmptyDocumentCode(KnowledgeBaseDataIsolation $dataIsolation, ?int $lastId = null, int $pageSize = 500): array
    {
        return $this->knowledgeBaseFragmentRepository->getFragmentsByEmptyDocumentCode($dataIsolation, $lastId, $pageSize);
    }

    #[Transactional]
    public function updateWordCount(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseFragmentEntity $entity, int $deltaWordCount): void
    {
        // updatedatabaseword countstatistics
        $this->knowledgeBaseRepository->updateWordCount($dataIsolation, $entity->getKnowledgeCode(), $deltaWordCount);
        // updatedocumentword countstatistics
        $this->knowledgeBaseDocumentRepository->updateWordCount($dataIsolation, $entity->getKnowledgeCode(), $entity->getDocumentCode(), $deltaWordCount);
    }
}
