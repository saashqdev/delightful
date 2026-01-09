<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\KnowledgeBase\Service;

use App\Domain\Flow\Entity\ValueObject\Query\KnowledgeBaseDocumentQuery;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseDocumentEntity;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeType;
use App\Domain\KnowledgeBase\Entity\ValueObject\Query\KnowledgeBaseFragmentQuery;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseDocumentSavedEvent;
use App\ErrorCode\PermissionErrorCode;
use App\Infrastructure\Core\Embeddings\VectorStores\VectorStoreDriver;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Delightful\AsyncEvent\AsyncEventUtil;
use Qbhy\HyperfAuth\Authenticatable;

class KnowledgeBaseDocumentAppService extends AbstractKnowledgeAppService
{
    /**
     * @return array<string, int> array<knowledge basecode, documentquantity>
     */
    public function getDocumentCountByKnowledgeBaseCodes(Authenticatable $authorization, array $knowledgeBaseCodes): array
    {
        return $this->knowledgeBaseDocumentDomainService->getDocumentCountByKnowledgeBaseCodes($this->createKnowledgeBaseDataIsolation($authorization), $knowledgeBaseCodes);
    }

    /**
     * saveknowledge basedocument.
     */
    public function save(Authenticatable $authorization, KnowledgeBaseDocumentEntity $documentEntity): KnowledgeBaseDocumentEntity
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);
        $this->checkKnowledgeBaseOperation($dataIsolation, 'w', $documentEntity->getKnowledgeBaseCode(), $documentEntity->getCode());
        $documentEntity->setCreatedUid($dataIsolation->getCurrentUserId());
        $documentEntity->setUpdatedUid($dataIsolation->getCurrentUserId());
        $knowledgeBaseEntity = $this->knowledgeBaseDomainService->show($dataIsolation, $documentEntity->getKnowledgeBaseCode());

        // documentconfigurationinheritknowledge base(ifnothaveto应set)
        empty($knowledgeBaseEntity->getFragmentConfig()) && $documentEntity->setFragmentConfig($knowledgeBaseEntity->getFragmentConfig());
        empty($documentEntity->getRetrieveConfig()) && $documentEntity->setRetrieveConfig($knowledgeBaseEntity->getRetrieveConfig());

        // 嵌入configurationnotcanedit
        $documentEntity->setEmbeddingConfig($knowledgeBaseEntity->getEmbeddingConfig());
        // setdefault嵌入modelandtoquantitydatabase
        $documentEntity->setEmbeddingModel($knowledgeBaseEntity->getModel());
        $documentEntity->setVectorDb(VectorStoreDriver::default()->value);
        if (! $documentEntity->getCode()) {
            // 新建document
            if ($documentEntity->getDocumentFile()) {
                $documentFile = $this->documentFileStrategy->preProcessDocumentFile($dataIsolation, $documentEntity->getDocumentFile());
                $documentEntity->setDocumentFile($documentFile);
            }
            return $this->knowledgeBaseDocumentDomainService->create($dataIsolation, $knowledgeBaseEntity, $documentEntity);
        }
        return $this->knowledgeBaseDocumentDomainService->update($dataIsolation, $knowledgeBaseEntity, $documentEntity);
    }

    /**
     * queryknowledge basedocumentlist.
     *
     * @return array{total: int, list: array<KnowledgeBaseDocumentEntity>}
     */
    public function query(Authenticatable $authorization, KnowledgeBaseDocumentQuery $query, Page $page): array
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);

        // verifyknowledge basepermission
        $this->checkKnowledgeBaseOperation($dataIsolation, 'r', $query->getKnowledgeBaseCode(), $query->getCode());

        // compatible旧data，newdefaultdocument
        $fragmentQuery = new KnowledgeBaseFragmentQuery();
        $fragmentQuery->setKnowledgeCode($query->getKnowledgeBaseCode());
        $fragmentQuery->setIsDefaultDocumentCode(true);
        $fragmentEntities = $this->knowledgeBaseFragmentDomainService->queries($dataIsolation, $fragmentQuery, new Page(1, 1));
        if (! empty($fragmentEntities['list'])) {
            $knowledgeBaseEntity = $this->knowledgeBaseDomainService->show($dataIsolation, $query->getKnowledgeBaseCode());
            $this->knowledgeBaseStrategy->getOrCreateDefaultDocument($dataIsolation, $knowledgeBaseEntity);
        }

        // call领域servicequerydocument
        $entities = $this->knowledgeBaseDocumentDomainService->queries($dataIsolation, $query, $page);
        $documentCodeFinalSyncStatusMap = $this->knowledgeBaseFragmentDomainService->getFinalSyncStatusByDocumentCodes(
            $dataIsolation,
            array_map(fn ($entity) => $entity->getCode(), $entities['list'])
        );
        // getdocumentsyncstatus
        foreach ($entities['list'] as $entity) {
            if (isset($documentCodeFinalSyncStatusMap[$entity->getCode()])) {
                $entity->setSyncStatus($documentCodeFinalSyncStatusMap[$entity->getCode()]->value);
            }
        }
        return $entities;
    }

    public function reVectorizedByThirdFileId(Authenticatable $authorization, string $thirdPlatformType, string $thirdFileId): void
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);
        $documents = $this->knowledgeBaseDocumentDomainService->getByThirdFileId($dataIsolation, $thirdPlatformType, $thirdFileId);
        $knowledgeEntities = $this->knowledgeBaseDomainService->getByCodes($dataIsolation, array_column($documents, 'knowledge_base_code'));
        /** @var array<string, KnowledgeBaseEntity> $knowledgeEntities */
        $knowledgeEntities = array_column($knowledgeEntities, null, 'code');

        foreach ($documents as $document) {
            $knowledgeEntity = $knowledgeEntities[$document['knowledge_base_code']] ?? null;
            if ($knowledgeEntity && $knowledgeEntity->getType() === KnowledgeType::UserKnowledgeBase->value) {
                $event = new KnowledgeBaseDocumentSavedEvent($dataIsolation, $knowledgeEntity, $document, false);
                AsyncEventUtil::dispatch($event);
            }
        }
    }

    /**
     * viewsingleknowledge basedocumentdetail.
     */
    public function show(Authenticatable $authorization, string $knowledgeBaseCode, string $documentCode): KnowledgeBaseDocumentEntity
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);
        $this->checkKnowledgeBaseOperation($dataIsolation, 'r', $knowledgeBaseCode, $documentCode);

        // getdocument
        $entity = $this->knowledgeBaseDocumentDomainService->show($dataIsolation, $knowledgeBaseCode, $documentCode);
        $documentCodeFinalSyncStatusMap = $this->knowledgeBaseFragmentDomainService->getFinalSyncStatusByDocumentCodes($dataIsolation, [$documentCode]);
        isset($documentCodeFinalSyncStatusMap[$documentCode]) && $entity->setSyncStatus($documentCodeFinalSyncStatusMap[$documentCode]->value);
        return $entity;
    }

    /**
     * deleteknowledge basedocument.
     */
    public function destroy(Authenticatable $authorization, string $knowledgeBaseCode, string $documentCode): void
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);
        $this->checkKnowledgeBaseOperation($dataIsolation, 'del', $knowledgeBaseCode, $documentCode);
        $knowledgeBaseEntity = $this->knowledgeBaseDomainService->show($dataIsolation, $knowledgeBaseCode);

        // call领域servicedeletedocument
        $this->knowledgeBaseDocumentDomainService->destroy($dataIsolation, $knowledgeBaseEntity, $documentCode);
    }

    /**
     * 重新toquantity化.
     */
    public function reVectorized(Authenticatable $authorization, string $knowledgeBaseCode, string $documentCode): void
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);
        $this->checkKnowledgeBaseOperation($dataIsolation, 'manage', $knowledgeBaseCode, $documentCode);

        // call领域service重新toquantity化
        $knowledgeBaseEntity = $this->knowledgeBaseDomainService->show($dataIsolation, $knowledgeBaseCode);
        $documentEntity = $this->knowledgeBaseDocumentDomainService->show($dataIsolation, $knowledgeBaseCode, $documentCode);
        // byathistorydocumentnothave document_file field，not能be重新toquantity化
        if (! $documentEntity->getDocumentFile()) {
            ExceptionBuilder::throw(PermissionErrorCode::Error, 'flow.knowledge_base.re_vectorized_not_support');
        }
        // minutehairevent，重新toquantity化
        $documentSavedEvent = new KnowledgeBaseDocumentSavedEvent(
            $dataIsolation,
            $knowledgeBaseEntity,
            $documentEntity,
            false,
        );
        AsyncEventUtil::dispatch($documentSavedEvent);
    }
}
