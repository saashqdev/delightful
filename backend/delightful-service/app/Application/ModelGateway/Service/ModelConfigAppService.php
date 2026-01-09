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
     * getmodel降levelchain,mergeuserpass in降levelchainandsystemdefault降levelchain.
     *
     * @param string $orgCode organizationencoding
     * @param string $userId userID
     * @param string $modelType fingersetmodeltype
     * @param string[] $modelFallbackChain userpass in降levelchain
     *
     * @return string finalmodeltype
     */
    public function getChatModelTypeByFallbackChain(string $orgCode, string $userId, string $modelType = '', array $modelFallbackChain = []): string
    {
        $dataIsolation = ModelGatewayDataIsolation::createByOrganizationCodeWithoutSubscription($orgCode, $userId);
        // fromorganizationcanusemodellistmiddleget havecanchatmodel
        $odinModels = di(ModelGatewayMapper::class)->getChatModels($dataIsolation) ?? [];
        $chatModelsName = array_keys($odinModels);
        if (empty($chatModelsName)) {
            return '';
        }

        // iffingersetmodeltypeandthemodelexistsinatcanusemodellistmiddle,thendirectlyreturn
        if (! empty($modelType) && in_array($modelType, $chatModelsName)) {
            return $modelType;
        }

        // willcanusemodeltransferforhashtable,implementO(1)timecomplexdegreefind
        $availableModels = array_flip($chatModelsName);

        // getsystemdefault降levelchain
        $systemFallbackChain = config('delightful-api.model_fallback_chain.chat', []);

        // mergeuserpass in降levelchainandsystemdefault降levelchain
        // userpass in降levelchainprioritylevelmorehigh
        $mergedFallbackChain = array_merge($systemFallbackChain, $modelFallbackChain);

        // 按prioritylevelordertraversemergeback降levelchain
        foreach ($mergedFallbackChain as $modelName) {
            if (isset($availableModels[$modelName])) {
                return $modelName;
            }
        }

        // back备solution:ifnothavematchanyprioritymodel,usefirstcanusemodel
        return $chatModelsName[0] ?? '';
    }
}
