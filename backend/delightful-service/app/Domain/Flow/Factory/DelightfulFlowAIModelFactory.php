<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Factory;

use App\Domain\Flow\Entity\DelightfulFlowAIModelEntity;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowAIModelModel;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Hyperf\Odin\Contract\Model\EmbeddingInterface;
use Hyperf\Odin\Contract\Model\ModelInterface;
use Hyperf\Odin\Factory\ModelFactory;
use Hyperf\Odin\Model\ModelOptions;

class DelightfulFlowAIModelFactory
{
    public static function modelToEntity(DelightfulFlowAIModelModel $model): DelightfulFlowAIModelEntity
    {
        $entity = new DelightfulFlowAIModelEntity();
        $entity->setId($model->id);
        $entity->setOrganizationCode($model->organization_code);
        $entity->setName($model->name);
        $entity->setLabel($model->label);
        $entity->setIcon($model->icon);
        $entity->setModelName($model->model_name);
        $entity->setTags($model->tags);
        $entity->setDefaultConfigs($model->default_configs);
        $entity->setEnabled($model->enabled);
        $entity->setDisplay($model->display);
        $entity->setImplementation($model->implementation);
        $entity->setImplementationConfig($model->implementation_config);
        $entity->setSupportEmbedding($model->support_embedding);
        $entity->setSupportMultiModal($model->support_multi_modal);
        $entity->setVectorSize($model->vector_size);
        $entity->setMaxTokens($model->max_tokens);
        $entity->setCreatedUid($model->created_uid);
        $entity->setCreatedAt($model->created_at);
        $entity->setUpdatedUid($model->updated_uid);
        $entity->setUpdatedAt($model->updated_at);
        return $entity;
    }

    public static function createOdinModel(DelightfulFlowAIModelEntity $delightfulFlowAIModelEntity): EmbeddingInterface|ModelInterface
    {
        if (! $delightfulFlowAIModelEntity->isEnabled()) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.model.disabled', ['model_name' => $delightfulFlowAIModelEntity->getName()]);
        }
        $modelName = $delightfulFlowAIModelEntity->getModelName() ?: $delightfulFlowAIModelEntity->getName();
        return ModelFactory::create(
            $delightfulFlowAIModelEntity->getImplementation(),
            $modelName,
            $delightfulFlowAIModelEntity->getActualImplementationConfig(),
            new ModelOptions([
                'embedding' => $delightfulFlowAIModelEntity->isSupportEmbedding(),
                'multi_modal' => $delightfulFlowAIModelEntity->isSupportMultiModal(),
                'function_call' => true,
                'vector_size' => $delightfulFlowAIModelEntity->getVectorSize(),
            ])
        );
    }
}
