<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\KnowledgeBase\Service;

use App\Domain\KnowledgeBase\Entity\KnowledgeBaseDocumentEntity;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseFragmentEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeBaseDataIsolation;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeSyncStatus;
use App\Domain\KnowledgeBase\Entity\ValueObject\Query\KnowledgeBaseFragmentQuery;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Throwable;

class KnowledgeBaseVectorAppService extends AbstractKnowledgeAppService
{
    /**
     * checkknowledge basetoquantitysetwhetherexistsin.
     *
     * @throws BusinessException
     */
    public function checkCollectionExists(KnowledgeBaseEntity $knowledgeBaseEntity): bool
    {
        $vector = $knowledgeBaseEntity->getVectorDBDriver();
        $collection = $vector->getCollection($knowledgeBaseEntity->getCollectionName());
        if (! $collection) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'collectionnotexistsin');
        }
        return true;
    }

    /**
     * destroyoldminutesegment.
     */
    public function destroyOldFragments(
        KnowledgeBaseDataIsolation $dataIsolation,
        KnowledgeBaseEntity $knowledge,
        KnowledgeBaseDocumentEntity $documentEntity
    ): bool {
        try {
            // firstget haveminutesegment
            $fragmentQuery = new KnowledgeBaseFragmentQuery();
            $fragmentQuery->setKnowledgeCode($knowledge->getCode());
            $fragmentQuery->setDocumentCode($documentEntity->getCode());
            $fragmentQuery->setVersion($documentEntity->getVersion());
            $fragmentEntities = [];
            $page = new Page(1, 1);
            while (true) {
                $currentFragmentEntities = $this->knowledgeBaseFragmentDomainService->queries($dataIsolation, $fragmentQuery, $page)['list'];
                if (empty($currentFragmentEntities)) {
                    break;
                }
                $fragmentEntities[] = $currentFragmentEntities;
                $page->setNextPage();
            }
            /**
             * @var array<KnowledgeBaseFragmentEntity> $fragmentEntities
             */
            $fragmentEntities = array_merge(...$fragmentEntities);

            // againdeleteslicesegment
            foreach ($fragmentEntities as $fragmentEntity) {
                $this->knowledgeBaseFragmentDomainService->destroy($dataIsolation, $knowledge, $fragmentEntity);
            }
            $documentEntity->setSyncStatus(KnowledgeSyncStatus::Deleted->value);
            $this->knowledgeBaseDocumentDomainService->changeSyncStatus($dataIsolation, $documentEntity);
            return true;
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getMessage() . PHP_EOL . $throwable->getTraceAsString());
            $documentEntity->setSyncStatus(KnowledgeSyncStatus::DeleteFailed->value);
            $documentEntity->setSyncStatusMessage($throwable->getMessage());
            $this->knowledgeBaseDocumentDomainService->changeSyncStatus($dataIsolation, $documentEntity);
            return false;
        }
    }

    public function syncDocument(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $knowledgeBaseEntity, KnowledgeBaseDocumentEntity $documentEntity): void
    {
        $documentFile = $documentEntity->getDocumentFile();
        if (! $documentFile) {
            return;
        }

        $documentEntity->setSyncStatus(KnowledgeSyncStatus::Syncing->value);
        $this->knowledgeBaseDocumentDomainService->changeSyncStatus($dataIsolation, $documentEntity);
        $this->logger->info('justinparsefile,filename:' . $documentFile->getName());
        $content = $this->documentFileStrategy->parseContent($dataIsolation, $documentFile, $knowledgeBaseEntity->getCode());
        $this->logger->info('parsefilecomplete,justinfileminutesegment,filename:' . $documentFile->getName());
        $splitText = $this->knowledgeBaseFragmentDomainService->processFragmentsByContent($dataIsolation, $content, $documentEntity->getFragmentConfig());
        $this->logger->info('fileminutesegmentcomplete,filename:' . $documentFile->getName() . ',minutesegmentquantity:' . count($splitText));

        foreach ($splitText as $text) {
            $fragmentEntity = (new KnowledgeBaseFragmentEntity())
                ->setKnowledgeCode($knowledgeBaseEntity->getCode())
                ->setDocumentCode($documentEntity->getCode())
                ->setContent($text)
                ->setCreator($documentEntity->getCreatedUid())
                ->setModifier($documentEntity->getUpdatedUid())
                ->setVersion($documentEntity->getVersion());
            $knowledgeBaseEntity = $this->knowledgeBaseDomainService->show($dataIsolation, $fragmentEntity->getKnowledgeCode());
            $this->knowledgeBaseFragmentDomainService->save($dataIsolation, $knowledgeBaseEntity, $documentEntity, $fragmentEntity);
        }
        $documentEntity->setSyncStatus(KnowledgeSyncStatus::Synced->value);
        $this->knowledgeBaseDocumentDomainService->changeSyncStatus($dataIsolation, $documentEntity);
    }
}
