<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\KnowledgeBase\Event\Subscribe;

use App\Domain\KnowledgeBase\Entity\KnowledgeBaseFragmentEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeType;
use App\Domain\KnowledgeBase\Entity\ValueObject\Query\KnowledgeBaseFragmentQuery;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseDefaultDocumentSavedEvent;
use App\Domain\KnowledgeBase\Service\KnowledgeBaseFragmentDomainService;
use App\Infrastructure\Core\ValueObject\Page;
use Delightful\AsyncEvent\Kernel\Annotation\AsyncListener;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function di;

#[AsyncListener]
#[Listener]
readonly class KnowledgeBaseDefaultDocumentSavedSubscriber implements ListenerInterface
{
    public function __construct()
    {
    }

    public function listen(): array
    {
        return [
            KnowledgeBaseDefaultDocumentSavedEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof KnowledgeBaseDefaultDocumentSavedEvent) {
            return;
        }
        $knowledge = $event->knowledgeBaseEntity;
        $documentEntity = $event->knowledgeBaseDocumentEntity;
        $dataIsolation = $event->dataIsolation;
        // ifis基础knowledge basetype，then传knowledge basecreate者，avoidpermissionnot足
        if (in_array($knowledge->getType(), KnowledgeType::getAll())) {
            $dataIsolation->setCurrentUserId($knowledge->getCreator())->setCurrentOrganizationCode($knowledge->getOrganizationCode());
        }
        /** @var KnowledgeBaseFragmentDomainService $knowledgeBaseFragmentDomainService */
        $knowledgeBaseFragmentDomainService = di(KnowledgeBaseFragmentDomainService::class);

        /** @var LoggerInterface $logger */
        $logger = di(LoggerInterface::class);

        try {
            $query = new KnowledgeBaseFragmentQuery();
            $query->setKnowledgeCode($knowledge->getCode());
            $query->setIsDefaultDocumentCode(true);
            $query->setDocumentCode($documentEntity->getCode());
            $page = new Page(1, 50);

            $fragments = [];
            while (true) {
                $res = $knowledgeBaseFragmentDomainService->queries($dataIsolation, $query, $page);
                if (empty($res['list'])) {
                    break;
                }
                $fragments[] = $res['list'];
                $page->setNextPage();
            }
            /** @var array<KnowledgeBaseFragmentEntity> $fragments */
            $fragments = array_merge(...$fragments);
            foreach ($fragments as $fragment) {
                $fragment->setDocumentCode($documentEntity->getCode())
                    ->setWordCount(mb_strlen($fragment->getContent()));
                $knowledgeBaseFragmentDomainService->save($dataIsolation, $knowledge, $documentEntity, $fragment);
            }
        } catch (Throwable $throwable) {
            $logger->error($throwable->getMessage() . PHP_EOL . $throwable->getTraceAsString());
        }
    }
}
