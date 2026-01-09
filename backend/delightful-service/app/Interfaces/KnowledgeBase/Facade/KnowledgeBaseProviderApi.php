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
     * get官方重sortprovidequotientcolumntable.
     * @return array<ProviderConfigModelsDTO>
     */
    public function getOfficialRerankProviderList(): array
    {
        $dto = new ProviderConfigModelsDTO();
        $dto->setId('official_rerank');
        $dto->setName('官方重sortservicequotient');
        $dto->setProviderType(ProviderType::Official->value);
        $dto->setDescription('官方provide重sortservice');
        $dto->setIcon('');
        $dto->setCategory('rerank');
        $dto->setStatus(1); // 1 indicateenable
        $dto->setCreatedAt(date('Y-m-d H:i:s'));

        // settingmodelcolumntable
        $models = [];

        // 基础重sortmodel
        $baseModel = new ProviderModelDetailDTO();
        $baseModel->setId('official_rerank_model');
        $baseModel->setName('官方重rowmodel');
        $baseModel->setModelVersion('v1.0');
        $baseModel->setDescription('基础重sortmodel,适useatgeneral场景');
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
     * get嵌入providequotientcolumntable.
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
