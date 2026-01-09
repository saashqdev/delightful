<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\KnowledgeBase\VectorDatabase\Similarity\Driver;

use App\Application\KnowledgeBase\VectorDatabase\Similarity\KnowledgeSimilarityFilter;
use App\Application\ModelGateway\Mapper\ModelGatewayMapper;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseFragmentEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeBaseDataIsolation;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeRetrievalResult;
use App\Domain\KnowledgeBase\Entity\ValueObject\RetrieveConfig;
use App\Infrastructure\Core\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;
use App\Infrastructure\Core\Embeddings\Rerank\RerankGeneratorInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class BaseSemanticSimilaritySearch implements SemanticSimilaritySearchInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    public function search(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeSimilarityFilter $filter, KnowledgeBaseEntity $knowledgeBaseEntity, RetrieveConfig $retrieveConfig): array
    {
        // 场景verify， 如果开启重新sort，can多召回data，然后according to得分进行sort，取 limit ，at most不超过 20 或者 limit 上限
        $queryNum = $filter->getLimit();
        if ($retrieveConfig->isRerankingEnable()) {
            // 如果开启重sort，增加召回quantity，但不超过20或originallimit的3倍
            $maxLimit = min(20, $queryNum * 3);
            $filter->setLimit($maxLimit);
        }
        // 兜底方案
        $question = $filter->getQuestion();
        if ($question === '') {
            $question = $filter->getQuery();
        }

        $modelGatewayMapper = di(ModelGatewayMapper::class);

        $result = [];
        // according tomodel进行向量化
        $model = $modelGatewayMapper->getEmbeddingModelProxy($dataIsolation, $knowledgeBaseEntity->getModel());
        $embeddingGenerator = di(EmbeddingGeneratorInterface::class);
        $queryEmbeddings = $embeddingGenerator->embedText($model, $question, options: [
            'business_params' => [
                'organization_id' => $dataIsolation->getCurrentOrganizationCode(),
                'user_id' => $dataIsolation->getCurrentUserId(),
                'business_id' => $knowledgeBaseEntity->getCode(),
                'source_id' => 'semantic_search',
            ],
        ]);
        $points = $knowledgeBaseEntity->getVectorDBDriver()->searchPoints(
            $knowledgeBaseEntity->getCollectionName(),
            $queryEmbeddings,
            $queryNum,
            $filter->getScore(),
            $filter->getMetadataFilter(),
        );
        foreach ($points as $point) {
            $fragment = KnowledgeBaseFragmentEntity::createByPointInfo($point, $knowledgeBaseEntity->getCode());
            $result[] = KnowledgeRetrievalResult::fromFragment((string) $fragment->getId(), $fragment->getContent(), $fragment->getBusinessId(), $fragment->getMetadata(), $fragment->getScore());
            if (count($result) >= $filter->getLimit()) {
                break;
            }
        }

        // todo optimize
        //        // 进行重sort
        //        if (count($result) > 1 && $retrieveConfig->isRerankingEnable() && container()->has(RerankGeneratorInterface::class)) {
        //            $rerankModelName = $retrieveConfig->getRerankingModel()["reranking_model_name'"];
        //            $rerankModel = OdinModelFactory::getFlowModelEntity($rerankModelName, $dataIsolation->getCurrentOrganizationCode());
        //            $documents = [];
        //            foreach ($result as $item) {
        //                $documents[] = $item->getContent();
        //            }
        //            $rerankGenerator = di(RerankGeneratorInterface::class);
        //            $rerankResult = $rerankGenerator->rerank($rerankModel->createRerank(), $filter->getQuestion(), $documents);
        //            // 按 relevance_score 从大到小sort
        //            usort($rerankResult, function ($a, $b) {
        //                return $b['relevance_score'] <=> $a['relevance_score'];
        //            });
        //
        //            // according tosort后的result重新排列 $result array
        //            $sortedResult = [];
        //            foreach ($rerankResult as $item) {
        //                $sortedResult[] = $result[$item['index']];
        //            }
        //            $result = $sortedResult;
        //            // restore到original的 limit value
        //            if (count($result) > $filter->getLimit()) {
        //                $result = array_slice($result, 0, $filter->getLimit());
        //            }
        //        }

        return $result;
    }
}
