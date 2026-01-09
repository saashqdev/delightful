<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\KnowledgeBase\Event\Subscribe;

use App\Application\KnowledgeBase\Service\KnowledgeBaseVectorAppService;
use App\Application\ModelGateway\Mapper\ModelGatewayMapper;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeSyncStatus;
use App\Domain\KnowledgeBase\Event\KnowledgeBaseFragmentSavedEvent;
use App\Domain\KnowledgeBase\Service\KnowledgeBaseDomainService;
use App\Domain\ModelGateway\Entity\ValueObject\ModelGatewayDataIsolation;
use App\Infrastructure\Core\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use Delightful\AsyncEvent\Kernel\Annotation\AsyncListener;
use Hyperf\Codec\Json;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Throwable;

use function di;

#[AsyncListener]
#[Listener]
readonly class KnowledgeBaseFragmentSyncSubscriber implements ListenerInterface
{
    public function __construct()
    {
    }

    public function listen(): array
    {
        return [
            KnowledgeBaseFragmentSavedEvent::class,
        ];
    }

    public function process(object $event): void
    {
        if (! $event instanceof KnowledgeBaseFragmentSavedEvent) {
            return;
        }
        $knowledge = $event->delightfulFlowKnowledgeEntity;
        $fragment = $event->delightfulFlowKnowledgeFragmentEntity;
        /** @var KnowledgeBaseDomainService $delightfulFlowKnowledgeDomainService */
        $delightfulFlowKnowledgeDomainService = di(KnowledgeBaseDomainService::class);
        /** @var KnowledgeBaseVectorAppService $knowledgeBaseVectorService */
        $knowledgeBaseVectorService = di(KnowledgeBaseVectorAppService::class);

        // todo makebecomequeuelimitstream

        try {
            $knowledgeBaseVectorService->checkCollectionExists($knowledge);

            // ifwithhavetoquantity,thennot重newembedding
            if (empty($fragment->getVector())) {
                $fragment->setSyncStatus(KnowledgeSyncStatus::Syncing);
                $delightfulFlowKnowledgeDomainService->changeSyncStatus($fragment);

                $modelGatewayDataIsolation = ModelGatewayDataIsolation::createByOrganizationCodeWithoutSubscription($knowledge->getOrganizationCode(), $knowledge->getCreator());
                $model = di(ModelGatewayMapper::class)->getEmbeddingModelProxy($modelGatewayDataIsolation, $knowledge->getModel());
                $embeddingGenerator = di(EmbeddingGeneratorInterface::class);
                $embeddings = $embeddingGenerator->embedText($model, $fragment->getContent(), options: [
                    'business_params' => [
                        'organization_id' => $knowledge->getOrganizationCode(),
                        'user_id' => $fragment->getModifier(),
                        'business_id' => $knowledge->getCode(),
                        'source_id' => 'fragment_saved',
                        'knowledge_info' => [
                            'id' => $knowledge->getId(),
                            'organization_code' => $knowledge->getOrganizationCode(),
                            'code' => $knowledge->getCode(),
                            'name' => $knowledge->getName(),
                            'business_id' => $knowledge->getBusinessId(),
                        ],
                    ],
                ]);
                $fragment->setVector(Json::encode($embeddings));
            } else {
                $embeddings = Json::decode($fragment->getVector());
            }

            $knowledge->getVectorDBDriver()->storePoint($knowledge->getCollectionName(), $fragment->getPointId(), $embeddings, $fragment->getPayload());

            $fragment->setSyncStatus(KnowledgeSyncStatus::Synced);
            $delightfulFlowKnowledgeDomainService->changeSyncStatus($fragment);
        } catch (Throwable $throwable) {
            $fragment->setSyncStatus(KnowledgeSyncStatus::SyncFailed);
            $fragment->setSyncStatusMessage($throwable->getMessage());
            $delightfulFlowKnowledgeDomainService->changeSyncStatus($fragment);
        }
    }
}
