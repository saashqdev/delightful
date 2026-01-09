<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Service;

use App\Domain\Flow\Entity\ValueObject\Query\KnowledgeBaseDocumentQuery;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseDocumentEntity;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\DocType;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeBaseDataIsolation;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeSyncStatus;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseDefaultDocumentSavedEvent;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseDocumentRemovedEvent;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseDocumentSavedEvent;
use App\Domain\KnowledgeBase\Repository\Facade\KnowledgeBaseDocumentRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Embeddings\VectorStores\VectorStoreDriver;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Delightful\AsyncEvent\AsyncEventUtil;
use Hyperf\DbConnection\Db;

/**
 * knowledge basedocument领域service
 */
readonly class KnowledgeBaseDocumentDomainService
{
    public function __construct(
        private KnowledgeBaseDocumentRepositoryInterface $knowledgeBaseDocumentRepository,
    ) {
    }

    public function create(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $knowledgeBaseEntity, KnowledgeBaseDocumentEntity $documentEntity): KnowledgeBaseDocumentEntity
    {
        $this->prepareForCreation($documentEntity);
        $entity = $this->knowledgeBaseDocumentRepository->create($dataIsolation, $documentEntity);
        // ifhavefile，syncfile
        if ($documentEntity->getDocumentFile()) {
            $event = new KnowledgeBaseDocumentSavedEvent($dataIsolation, $knowledgeBaseEntity, $entity, true);
            AsyncEventUtil::dispatch($event);
        }
        return $entity;
    }

    /**
     * @param array<KnowledgeBaseDocumentEntity> $documentEntities
     * @return array<KnowledgeBaseDocumentEntity>
     */
    public function upsert(KnowledgeBaseDataIsolation $dataIsolation, array $documentEntities): array
    {
        foreach ($documentEntities as $documentEntity) {
            $this->prepareForCreation($documentEntity);
        }
        $this->knowledgeBaseDocumentRepository->upsertByCode($dataIsolation, $documentEntities);
        return $documentEntities;
    }

    public function update(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $knowledgeBaseEntity, KnowledgeBaseDocumentEntity $documentEntity): KnowledgeBaseDocumentEntity
    {
        $oldDocument = $this->show($dataIsolation, $knowledgeBaseEntity->getCode(), $documentEntity->getCode());
        $this->prepareForUpdate($documentEntity, $oldDocument);
        return $this->knowledgeBaseDocumentRepository->update($dataIsolation, $documentEntity);
    }

    public function updateDocumentFile(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentEntity $documentEntity): void
    {
        $this->knowledgeBaseDocumentRepository->updateDocumentFile($dataIsolation, $documentEntity);
    }

    /**
     * queryknowledge basedocumentlist.
     *
     * @return array{total: int, list: array<KnowledgeBaseDocumentEntity>}
     */
    public function queries(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentQuery $query, Page $page): array
    {
        return $this->knowledgeBaseDocumentRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * view单knowledge basedocumentdetail.
     */
    public function show(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeBaseCode, string $documentCode, bool $selectForUpdate = false): KnowledgeBaseDocumentEntity
    {
        $document = $this->knowledgeBaseDocumentRepository->show($dataIsolation, $knowledgeBaseCode, $documentCode, $selectForUpdate);
        if ($document === null) {
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'common.not_found', ['label' => 'document']);
        }
        return $document;
    }

    /**
     * deleteknowledge basedocument.
     */
    public function destroy(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $knowledgeBaseEntity, string $documentCode): void
    {
        $documentEntity = null;
        Db::transaction(function () use ($dataIsolation, $documentCode, $knowledgeBaseEntity) {
            $knowledgeBaseCode = $knowledgeBaseEntity->getCode();
            // firstdeletedocumentdown所haveslicesegment
            $this->destroyFragments($dataIsolation, $knowledgeBaseCode, $documentCode);
            $documentEntity = $this->show($dataIsolation, $knowledgeBaseCode, $documentCode, true);
            // 然backdeletedocument本身
            $this->knowledgeBaseDocumentRepository->destroy($dataIsolation, $knowledgeBaseCode, $documentCode);
            // updatecharacter数
            $deltaWordCount = -$documentEntity->getWordCount();
            $this->updateWordCount($dataIsolation, $knowledgeBaseCode, $documentEntity->getCode(), $deltaWordCount);
        });
        // asyncdeletetoquantitydatabaseslicesegment
        /* @phpstan-ignore-next-line */
        ! is_null($documentEntity) && AsyncEventUtil::dispatch(new KnowledgeBaseDocumentRemovedEvent($dataIsolation, $knowledgeBaseEntity, $documentEntity));
    }

    /**
     * 重建knowledge basedocumenttoquantity索引.
     */
    public function rebuild(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeBaseCode, string $documentCode, bool $force = false): void
    {
        $document = $this->show($dataIsolation, $knowledgeBaseCode, $documentCode);

        // ifforce重建or者syncstatusforfail，then重新sync
        if ($force || $document->getSyncStatus() === 2) { // 2 table示syncfail
            $document->setSyncStatus(0); // 0 table示未sync
            $document->setSyncStatusMessage('');
            $document->setSyncTimes(0);
            $this->knowledgeBaseDocumentRepository->update($dataIsolation, $document);

            // async触hair重建（这withincansendeventor者加入queue）
            // TODO: 触hair重建toquantityevent
        }
    }

    public function updateWordCount(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeBaseCode, string $documentCode, int $deltaWordCount): void
    {
        if ($deltaWordCount === 0) {
            return;
        }
        $this->knowledgeBaseDocumentRepository->updateWordCount($dataIsolation, $knowledgeBaseCode, $documentCode, $deltaWordCount);
    }

    /**
     * @return array<string, int> array<knowledge basecode, documentquantity>
     */
    public function getDocumentCountByKnowledgeBaseCodes(KnowledgeBaseDataIsolation $dataIsolation, array $knowledgeBaseCodes): array
    {
        return $this->knowledgeBaseDocumentRepository->getDocumentCountByKnowledgeBaseCode($dataIsolation, $knowledgeBaseCodes);
    }

    /**
     * @return array<string, KnowledgeBaseDocumentEntity> array<documentcode, document名>
     */
    public function getDocumentsByCodes(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeBaseCode, array $documentCodes): array
    {
        return $this->knowledgeBaseDocumentRepository->getDocumentsByCodes($dataIsolation, $knowledgeBaseCode, $documentCodes);
    }

    public function changeSyncStatus(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentEntity $documentEntity): void
    {
        $this->knowledgeBaseDocumentRepository->changeSyncStatus($dataIsolation, $documentEntity);
    }

    public function getOrCreateDefaultDocument(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $knowledgeBaseEntity): KnowledgeBaseDocumentEntity
    {
        // 尝试getdefaultdocument
        $defaultDocumentCode = $knowledgeBaseEntity->getDefaultDocumentCode();
        $documentEntity = $this->knowledgeBaseDocumentRepository->show($dataIsolation, $knowledgeBaseEntity->getCode(), $defaultDocumentCode);
        if ($documentEntity) {
            return $documentEntity;
        }
        // ifdocumentnot存in，createnewdefaultdocument
        $documentEntity = (new KnowledgeBaseDocumentEntity())
            ->setCode($defaultDocumentCode)
            ->setName('未命名document.txt')
            ->setKnowledgeBaseCode($knowledgeBaseEntity->getCode())
            ->setCreatedUid($knowledgeBaseEntity->getCreator())
            ->setUpdatedUid($knowledgeBaseEntity->getCreator())
            ->setDocType(DocType::TXT->value)
            ->setSyncStatus(KnowledgeSyncStatus::Synced->value)
            ->setOrganizationCode($knowledgeBaseEntity->getOrganizationCode())
            ->setEmbeddingModel($knowledgeBaseEntity->getModel())
            ->setEmbeddingConfig($knowledgeBaseEntity->getEmbeddingConfig())
            ->setFragmentConfig($knowledgeBaseEntity->getFragmentConfig())
            ->setRetrieveConfig($knowledgeBaseEntity->getRetrieveConfig())
            ->setWordCount(0)
            ->setVectorDb(VectorStoreDriver::default()->value);
        $res = $this->knowledgeBaseDocumentRepository->restoreOrCreate($dataIsolation, $documentEntity);
        $event = new KnowledgeBaseDefaultDocumentSavedEvent($dataIsolation, $knowledgeBaseEntity, $documentEntity);
        AsyncEventUtil::dispatch($event);
        return $res;
    }

    public function increaseVersion(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentEntity $documentEntity): int
    {
        return $this->knowledgeBaseDocumentRepository->increaseVersion($dataIsolation, $documentEntity);
    }

    public function reVectorizedByThirdFileId(KnowledgeBaseDataIsolation $dataIsolation, string $thirdPlatformType, string $thirdFileId): void
    {
        /** @var KnowledgeBaseDocumentDomainService $knowledgeBaseDocumentDomainService */
        $knowledgeBaseDocumentDomainService = di(KnowledgeBaseDocumentDomainService::class);
        /** @var KnowledgeBaseDomainService $knowledgeBaseDomainService */
        $knowledgeBaseDomainService = di(KnowledgeBaseDomainService::class);

        $documents = $knowledgeBaseDocumentDomainService->getByThirdFileId($dataIsolation, $thirdPlatformType, $thirdFileId);
        $knowledgeEntities = $knowledgeBaseDomainService->getByCodes($dataIsolation, array_column($documents, 'knowledge_base_code'));

        foreach ($documents as $document) {
            $knowledgeEntity = $knowledgeEntities[$document['knowledge_base_code']] ?? null;
            if ($knowledgeEntity) {
                $event = new KnowledgeBaseDocumentSavedEvent($dataIsolation, $knowledgeEntity, $document, false);
                AsyncEventUtil::dispatch($event);
            }
        }
    }

    /**
     * @return array<KnowledgeBaseDocumentEntity>
     */
    public function getByThirdFileId(KnowledgeBaseDataIsolation $dataIsolation, string $thirdPlatformType, string $thirdFileId, ?string $knowledgeBaseCode = null): array
    {
        $loopCount = 20;
        $pageSize = 500;
        $lastId = null;
        /** @var array<KnowledgeBaseDocumentEntity> $res */
        $res = [];
        // at mostallowget一万sharedocument
        while ($loopCount--) {
            $entities = $this->knowledgeBaseDocumentRepository->getByThirdFileId($dataIsolation, $thirdPlatformType, $thirdFileId, $knowledgeBaseCode, $lastId, $pageSize);
            if (empty($entities)) {
                break;
            }
            $res = array_merge($res, $entities);
            $lastId = $entities[count($entities) - 1]->getId();
        }
        return $res;
    }

    /**
     * deletedocumentdown所haveslicesegment.
     */
    private function destroyFragments(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeBaseCode, string $documentCode): void
    {
        $this->knowledgeBaseDocumentRepository->destroyFragmentsByDocumentCode($dataIsolation, $knowledgeBaseCode, $documentCode);
    }

    /**
     * 准备create.
     */
    private function prepareForCreation(KnowledgeBaseDocumentEntity $documentEntity): void
    {
        if (empty($documentEntity->getName())) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'documentnamenot能for空');
        }

        if (empty($documentEntity->getKnowledgeBaseCode())) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'knowledge baseencodingnot能for空');
        }

        if (empty($documentEntity->getCreatedUid())) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'create者not能for空');
        }

        // setdefaultvalue
        if (! $documentEntity->issetCreatedAt()) {
            $documentEntity->setCreatedAt(date('Y-m-d H:i:s'));
        }

        $documentFile = $documentEntity->getDocumentFile();
        $documentEntity->setUpdatedAt($documentEntity->getCreatedAt());
        $documentEntity->setUpdatedUid($documentEntity->getCreatedUid());
        $documentEntity->setSyncStatus(0); // 0 table示未sync
        // bydownproperty均fromdocumentfilemiddleget
        $documentEntity->setDocType($documentFile?->getDocType() ?? DocType::TXT->value);
        $documentEntity->setThirdFileId($documentFile?->getThirdFileId());
        $documentEntity->setThirdPlatformType($documentFile?->getPlatformType());
    }

    /**
     * 准备update.
     */
    private function prepareForUpdate(KnowledgeBaseDocumentEntity $newDocument, KnowledgeBaseDocumentEntity $oldDocument): void
    {
        // notallowmodifyfield保持原value
        $newDocument->setId($oldDocument->getId());
        $newDocument->setCode($oldDocument->getCode());
        $newDocument->setKnowledgeBaseCode($oldDocument->getKnowledgeBaseCode());
        $newDocument->setCreatedAt($oldDocument->getCreatedAt());
        $newDocument->setCreatedUid($oldDocument->getCreatedUid());
        $newDocument->setDocType($oldDocument->getDocType());
        $newDocument->setWordCount($oldDocument->getWordCount());
        $newDocument->setSyncStatus($oldDocument->getSyncStatus());
        $newDocument->setSyncStatusMessage($oldDocument->getSyncStatusMessage());
        $newDocument->setSyncTimes($oldDocument->getSyncTimes());
        $newDocument->setDocumentFile($oldDocument->getDocumentFile());
        $newDocument->setThirdPlatformType($oldDocument->getThirdPlatformType());
        $newDocument->setThirdFileId($oldDocument->getThirdFileId());
        $newDocument->setVersion($oldDocument->getVersion());

        // updatetime
        $newDocument->setUpdatedAt(date('Y-m-d H:i:s'));
    }
}
