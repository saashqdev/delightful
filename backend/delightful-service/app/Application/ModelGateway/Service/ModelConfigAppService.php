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
     * according toIDgetmodelconfiguration.
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
     * getmodel的降level链，mergeuser传入的降level链与系统default的降level链.
     *
     * @param string $orgCode organizationencoding
     * @param string $userId userID
     * @param string $modelType finger定的modeltype
     * @param string[] $modelFallbackChain user传入的降level链
     *
     * @return string final的modeltype
     */
    public function getChatModelTypeByFallbackChain(string $orgCode, string $userId, string $modelType = '', array $modelFallbackChain = []): string
    {
        $dataIsolation = ModelGatewayDataIsolation::createByOrganizationCodeWithoutSubscription($orgCode, $userId);
        // fromorganization可use的modellistmiddleget所have可chat的model
        $odinModels = di(ModelGatewayMapper::class)->getChatModels($dataIsolation) ?? [];
        $chatModelsName = array_keys($odinModels);
        if (empty($chatModelsName)) {
            return '';
        }

        // iffinger定了modeltypeand该model存inat可usemodellistmiddle，then直接return
        if (! empty($modelType) && in_array($modelType, $chatModelsName)) {
            return $modelType;
        }

        // 将可usemodel转为hashtable，implementO(1)time复杂degree的查找
        $availableModels = array_flip($chatModelsName);

        // get系统default的降level链
        $systemFallbackChain = config('delightful-api.model_fallback_chain.chat', []);

        // mergeuser传入的降level链与系统default的降level链
        // user传入的降level链优先levelmore高
        $mergedFallbackChain = array_merge($systemFallbackChain, $modelFallbackChain);

        // 按优先level顺序遍历mergeback的降level链
        foreach ($mergedFallbackChain as $modelName) {
            if (isset($availableModels[$modelName])) {
                return $modelName;
            }
        }

        // back备solution：ifnothave匹配任何优先model，usefirst可usemodel
        return $chatModelsName[0] ?? '';
    }
}
