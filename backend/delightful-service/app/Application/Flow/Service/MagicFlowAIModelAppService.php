<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\Service;

use App\Application\ModelGateway\Mapper\ModelGatewayMapper;
use App\Domain\Flow\Entity\DelightfulFlowAIModelEntity;
use Hyperf\Odin\Model\AbstractModel;
use Qbhy\HyperfAuth\Authenticatable;

class DelightfulFlowAIModelAppService extends AbstractFlowAppService
{
    /**
     * @return array{total: int, list: array<DelightfulFlowAIModelEntity>}
     */
    public function getEnabled(Authenticatable $authorization): array
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        $mapper = di(ModelGatewayMapper::class);

        $list = [];
        $models = $mapper->getChatModels($dataIsolation);
        foreach ($models as $odinModel) {
            /** @var AbstractModel $model */
            $model = $odinModel->getModel();
            if ($model->getModelOptions()->isEmbedding()) {
                continue;
            }

            $modelEntity = new DelightfulFlowAIModelEntity();
            $modelEntity->setName($odinModel->getAttributes()->getName());
            $modelEntity->setModelName($model->getModelName());
            $modelEntity->setLabel($odinModel->getAttributes()->getLabel() ?: $odinModel->getAttributes()->getName());
            $modelEntity->setIcon($odinModel->getAttributes()->getIcon());
            $modelEntity->setTags($odinModel->getAttributes()->getTags());
            $modelEntity->setDefaultConfigs(['temperature' => 0.5]);
            $modelEntity->setSupportMultiModal($model->getModelOptions()->isMultiModal());
            $list[] = $modelEntity;
        }
        return [
            'total' => count($list),
            'list' => $list,
        ];
    }
}
