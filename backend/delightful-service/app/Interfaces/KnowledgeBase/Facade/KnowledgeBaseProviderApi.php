<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\KnowledgeBase\Facade;

use App\Domain\Provider\DTO\ProviderConfigModelsDTO;
use App\Domain\Provider\DTO\ProviderModelDetailDTO;
use App\Domain\Provider\Entity\ValueObject\ProviderType;
use App\Interfaces\KnowledgeBase\Assembler\KnowledgeBaseProviderAssembler;
use Delightful\ApiResponse\Annotation\ApiResponse;

#[ApiResponse(version: 'low_code')]
class KnowledgeBaseProviderApi extends AbstractKnowledgeBaseApi
{
    /**
     * get官方重sort提供商列表.
     * @return array<ProviderConfigModelsDTO>
     */
    public function getOfficialRerankProviderList(): array
    {
        $dto = new ProviderConfigModelsDTO();
        $dto->setId('official_rerank');
        $dto->setName('官方重sort服务商');
        $dto->setProviderType(ProviderType::Official->value);
        $dto->setDescription('官方提供的重sort服务');
        $dto->setIcon('');
        $dto->setCategory('rerank');
        $dto->setStatus(1); // 1 表示启用
        $dto->setCreatedAt(date('Y-m-d H:i:s'));

        // settingmodel列表
        $models = [];

        // 基础重sortmodel
        $baseModel = new ProviderModelDetailDTO();
        $baseModel->setId('official_rerank_model');
        $baseModel->setName('官方重排model');
        $baseModel->setModelVersion('v1.0');
        $baseModel->setDescription('基础重sortmodel，适用于一般场景');
        $baseModel->setIcon('');
        $baseModel->setModelType(1);
        $baseModel->setCategory('rerank');
        $baseModel->setStatus(1);
        $baseModel->setSort(1);
        $baseModel->setCreatedAt(date('Y-m-d H:i:s'));
        $models[] = $baseModel;

        $dto->setModels($models);

        return [$dto];
    }

    /**
     * get嵌入提供商列表.
     * @return array<ProviderConfigModelsDTO>
     */
    public function getEmbeddingProviderList(): array
    {
        $userAuthorization = $this->getAuthorization();
        /* @phpstan-ignore-next-line */
        $models = $this->llmAppService->models(accessToken: DELIGHTFUL_ACCESS_TOKEN, withInfo: true, type: 'embedding', businessParams: [
            'organization_code' => $userAuthorization->getOrganizationCode(),
            'user_id' => $userAuthorization->getId(),
        ]);
        return KnowledgeBaseProviderAssembler::odinModelToProviderDTO($models);
    }
}
