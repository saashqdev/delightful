<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\ModelGateway\Service;

use App\Application\Kernel\SuperPermissionEnum;
use App\Application\ModelGateway\Mapper\ModelGatewayMapper;
use App\Domain\ModelGateway\Entity\ModelConfigEntity;
use App\Domain\ModelGateway\Entity\ValueObject\ModelGatewayDataIsolation;
use App\Domain\ModelGateway\Entity\ValueObject\Query\ModelConfigQuery;
use App\Infrastructure\Core\ValueObject\Page;
use Qbhy\HyperfAuth\Authenticatable;

class ModelConfigAppService extends AbstractLLMAppService
{
    public function save(Authenticatable $authorization, ModelConfigEntity $modelConfigEntity): ModelConfigEntity
    {
        $this->checkInternalWhite($authorization, SuperPermissionEnum::MODEL_CONFIG_ADMIN);
        return $this->modelConfigDomainService->save($this->createLLMDataIsolation($authorization), $modelConfigEntity);
    }

    public function show(Authenticatable $authorization, string $model): ModelConfigEntity
    {
        $this->checkInternalWhite($authorization, SuperPermissionEnum::MODEL_CONFIG_ADMIN);
        return $this->modelConfigDomainService->show($this->createLLMDataIsolation($authorization), $model);
    }

    /**
     * 根据IDgetmodelconfiguration.
     */
    public function showById(Authenticatable $authorization, string $id): ModelConfigEntity
    {
        $this->checkInternalWhite($authorization, SuperPermissionEnum::MODEL_CONFIG_ADMIN);
        return $this->modelConfigDomainService->showById($id);
    }

    /**
     * @return ModelConfigEntity[]
     */
    public function queries(Authenticatable $authorization, ModelConfigQuery $query): array
    {
        $this->checkInternalWhite($authorization, SuperPermissionEnum::MODEL_CONFIG_ADMIN);
        return $this->modelConfigDomainService->queries($this->createLLMDataIsolation($authorization), Page::createNoPage(), $query)['list'];
    }

    public function enabledModels(Authenticatable $authorization): array
    {
        $query = new ModelConfigQuery();
        $query->setEnabled(true);
        $data = $this->modelConfigDomainService->queries($this->createLLMDataIsolation($authorization), Page::createNoPage(), $query);

        return array_map(function (ModelConfigEntity $modelConfigEntity) {
            return $modelConfigEntity->getModel();
        }, $data['list']);
    }

    /**
     * getmodel的降级链，合并user传入的降级链与系统默认的降级链.
     *
     * @param string $orgCode organization编码
     * @param string $userId userID
     * @param string $modelType 指定的modeltype
     * @param string[] $modelFallbackChain user传入的降级链
     *
     * @return string 最终的modeltype
     */
    public function getChatModelTypeByFallbackChain(string $orgCode, string $userId, string $modelType = '', array $modelFallbackChain = []): string
    {
        $dataIsolation = ModelGatewayDataIsolation::createByOrganizationCodeWithoutSubscription($orgCode, $userId);
        // 从organization可用的modellist中get所有可聊天的model
        $odinModels = di(ModelGatewayMapper::class)->getChatModels($dataIsolation) ?? [];
        $chatModelsName = array_keys($odinModels);
        if (empty($chatModelsName)) {
            return '';
        }

        // 如果指定了modeltype且该model存在于可用modellist中，则直接return
        if (! empty($modelType) && in_array($modelType, $chatModelsName)) {
            return $modelType;
        }

        // 将可用model转为哈希table，实现O(1)time复杂度的查找
        $availableModels = array_flip($chatModelsName);

        // get系统默认的降级链
        $systemFallbackChain = config('delightful-api.model_fallback_chain.chat', []);

        // 合并user传入的降级链与系统默认的降级链
        // user传入的降级链优先级更高
        $mergedFallbackChain = array_merge($systemFallbackChain, $modelFallbackChain);

        // 按优先级顺序遍历合并后的降级链
        foreach ($mergedFallbackChain as $modelName) {
            if (isset($availableModels[$modelName])) {
                return $modelName;
            }
        }

        // 后备方案：如果没有匹配任何优先model，使用第一个可用model
        return $chatModelsName[0] ?? '';
    }
}
