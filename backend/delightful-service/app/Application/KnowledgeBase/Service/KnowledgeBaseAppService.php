<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\KnowledgeBase\Service;

use App\Application\ModelGateway\Mapper\ModelGatewayMapper;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Flow\Entity\ValueObject\Code;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile\Interfaces\DocumentFileInterface;
use App\Domain\KnowledgeBase\Entity\ValueObject\Query\KnowledgeBaseQuery;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\Operation;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Embeddings\EmbeddingGenerator\EmbeddingGenerator;
use App\Infrastructure\Core\Embeddings\VectorStores\VectorStoreDriver;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Qbhy\HyperfAuth\Authenticatable;
use Throwable;

class KnowledgeBaseAppService extends AbstractKnowledgeAppService
{
    /**
     * @param array<DocumentFileInterface> $documentFiles
     */
    public function save(Authenticatable $authorization, KnowledgeBaseEntity $delightfulFlowKnowledgeEntity, array $documentFiles = []): KnowledgeBaseEntity
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);
        $delightfulFlowKnowledgeEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $delightfulFlowKnowledgeEntity->setCreator($dataIsolation->getCurrentUserId());

        $oldKnowledge = null;
        // if具有业务 id，那么就是update了，need先query出来
        if (! empty($delightfulFlowKnowledgeEntity->getBusinessId())) {
            $oldKnowledge = $this->getByBusinessId($authorization, $delightfulFlowKnowledgeEntity->getBusinessId());
            if ($oldKnowledge) {
                $delightfulFlowKnowledgeEntity->setCode($oldKnowledge->getCode());
            }
        }

        // updatedata - querypermission
        if (! $delightfulFlowKnowledgeEntity->shouldCreate() && ! $oldKnowledge) {
            $oldKnowledge = $this->knowledgeBaseDomainService->show($dataIsolation, $delightfulFlowKnowledgeEntity->getCode(), false);
        }
        $operation = Operation::None;
        if ($oldKnowledge) {
            $operation = $this->knowledgeBaseStrategy->getKnowledgeOperation($dataIsolation, $oldKnowledge->getCode());
            $operation->validate('w', $oldKnowledge->getCode());

            // use原来的model和向量library
            $delightfulFlowKnowledgeEntity->setModel($oldKnowledge->getModel());
            $delightfulFlowKnowledgeEntity->setVectorDB($oldKnowledge->getVectorDB());
        }
        $modelGatewayMapper = di(ModelGatewayMapper::class);

        // create的才needsetting
        if ($delightfulFlowKnowledgeEntity->shouldCreate()) {
            $modelId = $delightfulFlowKnowledgeEntity->getEmbeddingConfig()['model_id'] ?? null;
            if (! $modelId) {
                // 优先useconfiguration的model
                $modelId = EmbeddingGenerator::defaultModel();
                if (! $modelGatewayMapper->exists($dataIsolation, $modelId)) {
                    // get第one
                    $firstEmbeddingModel = $modelGatewayMapper->getEmbeddingModels($dataIsolation)[0] ?? null;
                    $modelId = $firstEmbeddingModel?->getKey();
                }
                // update嵌入configurationmodel_id
                $embeddingConfig = $delightfulFlowKnowledgeEntity->getEmbeddingConfig();
                $embeddingConfig['model_id'] = $modelId;
                $delightfulFlowKnowledgeEntity->setEmbeddingConfig($embeddingConfig);
            }
            if (! $modelId) {
                ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'flow.model.error_config_missing', ['name' => 'embedding_model']);
            }

            $delightfulFlowKnowledgeEntity->setModel($modelId);
            $delightfulFlowKnowledgeEntity->setVectorDB(VectorStoreDriver::default()->value);
        }

        $modelName = $delightfulFlowKnowledgeEntity->getModel();
        $delightfulFlowKnowledgeEntity->setForceCreateCode(Code::Knowledge->gen());
        // createknowledge base前，先对嵌入model进行连通性test
        try {
            $embeddingModel = di(ModelGatewayMapper::class)->getEmbeddingModelProxy($dataIsolation, $delightfulFlowKnowledgeEntity->getModel());
            $modelName = $embeddingModel->getModelName();
            $embeddingResult = $embeddingModel->embedding(
                'test.' . uniqid(),
                businessParams: [
                    'organization_id' => $dataIsolation->getCurrentOrganizationCode(),
                    'user_id' => $dataIsolation->getCurrentUserId(),
                    'business_id' => $delightfulFlowKnowledgeEntity->getForceCreateCode(),
                    'source_id' => 'knowledge_embedding_test',
                    'knowledge_info' => [
                        'id' => $delightfulFlowKnowledgeEntity->getId(),
                        'organization_code' => $dataIsolation->getCurrentOrganizationCode(),
                        'code' => $delightfulFlowKnowledgeEntity->getForceCreateCode(),
                        'name' => $delightfulFlowKnowledgeEntity->getName(),
                        'business_id' => $delightfulFlowKnowledgeEntity->getBusinessId(),
                    ],
                ]
            );
            if (count($embeddingResult->getEmbeddings()) !== $embeddingModel->getVectorSize()) {
                $actualSize = count($embeddingResult->getEmbeddings());
                $expectedSize = $embeddingModel->getVectorSize();
                ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'flow.model.vector_size_not_match', [
                    'model_name' => $modelName,
                    'expected_size' => $expectedSize,
                    'actual_size' => $actualSize,
                ]);
            }
        } catch (Throwable $exception) {
            simple_logger('KnowledgeBaseDomainService')->warning('KnowledgeBaseCheckEmbeddingsFailed', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ]);
            ExceptionBuilder::throw(FlowErrorCode::KnowledgeValidateFailed, 'flow.model.embedding_failed', [
                'model_name' => $modelName,
                'error_message' => $exception->getMessage(),
            ]);
        }

        $knowledgeBaseEntity = $this->knowledgeBaseDomainService->save($dataIsolation, $delightfulFlowKnowledgeEntity, $documentFiles);
        $knowledgeBaseEntity->setUserOperation($operation->value);
        $iconFileLink = $this->getFileLink($dataIsolation->getCurrentOrganizationCode(), $knowledgeBaseEntity->getIcon());
        $knowledgeBaseEntity->setIcon($iconFileLink?->getUrl() ?? '');
        $knowledgeBaseEntity->setSourceType($this->knowledgeBaseStrategy->getOrCreateDefaultSourceType($knowledgeBaseEntity));
        return $knowledgeBaseEntity;
    }

    public function saveProcess(Authenticatable $authorization, KnowledgeBaseEntity $savingKnowledgeEntity): KnowledgeBaseEntity
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);
        $savingKnowledgeEntity->setCreator($dataIsolation->getCurrentUserId());
        $this->checkKnowledgeBaseOperation($dataIsolation, 'w', $savingKnowledgeEntity->getCode());

        $entity = $this->knowledgeBaseDomainService->saveProcess($dataIsolation, $savingKnowledgeEntity);
        $entity->setSourceType($this->knowledgeBaseStrategy->getOrCreateDefaultSourceType($entity));
        return $entity;
    }

    public function getByBusinessId(Authenticatable $authorization, string $businessId, ?int $type = null): ?KnowledgeBaseEntity
    {
        if (empty($businessId)) {
            return null;
        }
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);
        $permissionDataIsolation = $this->createPermissionDataIsolation($dataIsolation);

        $resources = $this->operationPermissionAppService->getResourceOperationByUserIds(
            $permissionDataIsolation,
            ResourceType::Knowledge,
            [$authorization->getId()]
        )[$authorization->getId()] ?? [];
        $resourceIds = array_keys($resources);
        // 在这一堆中查找one
        $query = new KnowledgeBaseQuery();
        $query->setCodes($resourceIds);
        $query->setBusinessId($businessId);
        $query->setType($type);
        $result = $this->knowledgeBaseDomainService->queries($dataIsolation, $query, new Page(1, 1));
        $entity = $result['list'][0] ?? null;
        $entity && $entity->setSourceType($this->knowledgeBaseStrategy->getOrCreateDefaultSourceType($entity));
        return $entity;
    }

    /**
     * @return array{total: int, list: array<KnowledgeBaseEntity>, users: array<DelightfulUserEntity>}
     */
    public function queries(Authenticatable $authorization, KnowledgeBaseQuery $query, Page $page): array
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);

        $resources = $this->knowledgeBaseStrategy->getKnowledgeBaseOperations($dataIsolation);

        $query->setCodes(array_keys($resources));
        $result = $this->knowledgeBaseDomainService->queries($dataIsolation, $query, $page);
        $userIds = [];
        $iconFileLinks = $this->getIcons($dataIsolation->getCurrentOrganizationCode(), array_map(fn ($item) => $item->getIcon(), $result['list']));
        foreach ($result['list'] as $item) {
            $userIds[] = $item->getCreator();
            $userIds[] = $item->getModifier();
            $iconFileLink = $iconFileLinks[$item->getIcon()] ?? null;
            $item->setIcon($iconFileLink?->getUrl() ?? '');
            $item->setUserOperation(($resources[$item->getCode()] ?? Operation::None)->value);
            $item->setSourceType($this->knowledgeBaseStrategy->getOrCreateDefaultSourceType($item));
        }
        $result['users'] = $this->delightfulUserDomainService->getByUserIds($this->createContactDataIsolationByBase($dataIsolation), $userIds);
        return $result;
    }

    public function show(Authenticatable $authorization, string $code): KnowledgeBaseEntity
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);
        $operation = $this->checkKnowledgeBaseOperation($dataIsolation, 'r', $code);
        $knowledge = $this->knowledgeBaseDomainService->show($dataIsolation, $code, true);
        $knowledge->setUserOperation($operation->value);
        $knowledge->setSourceType($this->knowledgeBaseStrategy->getOrCreateDefaultSourceType($knowledge));
        $iconFileLink = $this->fileDomainService->getLink($dataIsolation->getCurrentOrganizationCode(), $knowledge->getIcon());
        $knowledge->setIcon($iconFileLink?->getUrl() ?? '');
        return $knowledge;
    }

    public function destroy(Authenticatable $authorization, string $code): void
    {
        $dataIsolation = $this->createKnowledgeBaseDataIsolation($authorization);
        $this->checkKnowledgeBaseOperation($dataIsolation, 'del', $code);
        $delightfulFlowKnowledgeEntity = $this->knowledgeBaseDomainService->show($dataIsolation, $code);
        $this->knowledgeBaseDomainService->destroy($dataIsolation, $delightfulFlowKnowledgeEntity);
    }
}
