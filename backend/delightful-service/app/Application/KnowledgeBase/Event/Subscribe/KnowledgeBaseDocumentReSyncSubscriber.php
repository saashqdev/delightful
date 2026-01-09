<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\KnowledgeBase\Event\Subscribe;

use App\Application\KnowledgeBase\Service\KnowledgeBaseVectorAppService;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeSyncStatus;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeType;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseDocumentSavedEvent;
use App\Domain\KnowledgeBase\Service\KnowledgeBaseDocumentDomainService;
use App\Infrastructure\Util\Locker\LockerInterface;
use Delightful\AsyncEvent\Kernel\Annotation\AsyncListener;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function di;

#[AsyncListener]
#[Listener]
readonly class KnowledgeBaseDocumentReSyncSubscriber implements ListenerInterface
{
    public function __construct()
    {
    }

    public function listen(): array
    {
        return [
            KnowledgeBaseDocumentSavedEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof KnowledgeBaseDocumentSavedEvent) {
            return;
        }
        if ($event->create) {
            return;
        }

        /** @var LockerInterface $lock */
        $lock = di(LockerInterface::class);
        $documentEntity = $event->knowledgeBaseDocumentEntity;
        /** @var LoggerInterface $logger */
        $logger = di(LoggerInterface::class);

        // getminute布typelock
        $lockKey = "document_re_sync:{$documentEntity->getKnowledgeBaseCode()}:{$documentEntity->getCode()}";
        if (! $lock->mutexLock($lockKey, $event->knowledgeBaseDocumentEntity->getCreatedUid(), 300)) { // 5minutesecondstimeout
            $logger->info('document[' . $documentEntity->getCode() . ']justinbeotherenter程process,skipsync');
            return;
        }

        try {
            $this->handle($event);
        } finally {
            $lock->release($lockKey, $event->knowledgeBaseDocumentEntity->getCreatedUid());
        }
    }

    private function handle(
        KnowledgeBaseDocumentSavedEvent $event
    ): void {
        $knowledge = $event->knowledgeBaseEntity;
        $documentEntity = $event->knowledgeBaseDocumentEntity;
        $dataIsolation = $event->dataIsolation;
        // ifisfoundationknowledge basetype,then传knowledge basecreateperson,avoidpermissionnot足
        if (in_array($knowledge->getType(), KnowledgeType::getAll())) {
            $dataIsolation->setCurrentUserId($knowledge->getCreator())->setCurrentOrganizationCode($knowledge->getOrganizationCode());
        }
        /** @var KnowledgeBaseDocumentDomainService $knowledgeBaseDocumentDomainService */
        $knowledgeBaseDocumentDomainService = di(KnowledgeBaseDocumentDomainService::class);
        /** @var LoggerInterface $logger */
        $logger = di(LoggerInterface::class);
        /** @var KnowledgeBaseVectorAppService $knowledgeBaseVectorAppService */
        $knowledgeBaseVectorAppService = di(KnowledgeBaseVectorAppService::class);

        // from增versionnumber(抢lock)
        $affectedRows = $knowledgeBaseDocumentDomainService->increaseVersion($dataIsolation, $documentEntity);
        // iffrom增fail,instructionalreadyalready重newtoquantity化pass,submitfrontend
        if ($affectedRows === 0) {
            $logger->info('documentalready重newtoquantity化,skipsync');
            return;
        }

        // checkconfiguration
        try {
            $knowledgeBaseVectorAppService->checkCollectionExists($knowledge);
        } catch (Throwable $throwable) {
            $logger->error($throwable->getMessage() . PHP_EOL . $throwable->getTraceAsString());
            $documentEntity->setSyncStatus(KnowledgeSyncStatus::SyncFailed->value);
            $documentEntity->setSyncStatusMessage($throwable->getMessage());
            $knowledgeBaseDocumentDomainService->changeSyncStatus($dataIsolation, $documentEntity);
            return;
        }

        // destroyoldminutesegment
        try {
            $knowledgeBaseVectorAppService->destroyOldFragments($dataIsolation, $knowledge, $documentEntity);
        } catch (Throwable $throwable) {
            $logger->error($throwable->getMessage() . PHP_EOL . $throwable->getTraceAsString());
            $documentEntity->setSyncStatus(KnowledgeSyncStatus::DeleteFailed->value);
            $documentEntity->setSyncStatusMessage($throwable->getMessage());
            $knowledgeBaseDocumentDomainService->changeSyncStatus($dataIsolation, $documentEntity);
            return;
        }

        // syncdocument
        try {
            $documentEntity->setVersion($documentEntity->getVersion() + 1);
            $knowledgeBaseVectorAppService->syncDocument($dataIsolation, $knowledge, $documentEntity);
        } catch (Throwable $throwable) {
            $logger->error($throwable->getMessage() . PHP_EOL . $throwable->getTraceAsString());
            $documentEntity->setSyncStatus(KnowledgeSyncStatus::SyncFailed->value);
            $documentEntity->setSyncStatusMessage($throwable->getMessage());
            $knowledgeBaseDocumentDomainService->changeSyncStatus($dataIsolation, $documentEntity);
        }
    }
}
