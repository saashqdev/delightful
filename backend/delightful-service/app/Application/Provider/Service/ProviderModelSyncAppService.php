<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Provider\Service;

use App\Domain\File\Service\FileDomainService;
use App\Domain\Provider\Entity\ProviderConfigEntity;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Service\ProviderConfigDomainService;
use App\Domain\Provider\Service\ProviderModelDomainService;
use App\Infrastructure\Util\DelightfulUriTool;
use App\Infrastructure\Util\SSRF\SSRFUtil;
use App\Interfaces\Provider\DTO\SaveProviderModelDTO;
use Delightful\CloudFile\Kernel\Struct\UploadFile;
use Delightful\CloudFile\Kernel\Utils\EasyFileTools;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Support\retry;

/**
 * service商model同步应用service.
 * 负责从外部API拉取model并同步到Officialservice商.
 */
class ProviderModelSyncAppService
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly ProviderConfigDomainService $providerConfigDomainService,
        private readonly ProviderModelDomainService $providerModelDomainService,
        private readonly ClientFactory $clientFactory,
        private readonly FileDomainService $fileDomainService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get('ProviderModelSync');
    }

    /**
     * 从外部API同步model.
     * 当service商configurationcreate或update时，如果是Officialservice商且是官方organization，则从外部API拉取model.
     */
    public function syncModelsFromExternalApi(
        ProviderConfigEntity $providerConfigEntity,
        string $language,
        string $organizationCode
    ): void {
        // 1. check是否为Officialservice商
        $dataIsolation = ProviderDataIsolation::create($organizationCode);
        $provider = $this->providerConfigDomainService->getProviderById($dataIsolation, $providerConfigEntity->getServiceProviderId());

        if (! $provider || $provider->getProviderCode() !== ProviderCode::Official) {
            $this->logger->debug('不是Officialservice商，跳过同步', [
                'config_id' => $providerConfigEntity->getId(),
                'provider_code' => $provider?->getProviderCode()->value,
            ]);
            return;
        }

        $this->logger->info('开始从外部API同步model', [
            'config_id' => $providerConfigEntity->getId(),
            'organization_code' => $organizationCode,
            'provider_code' => $provider->getProviderCode()->value,
        ]);

        try {
            // 3. 解析configuration
            $config = $providerConfigEntity->getConfig();
            if (! $config) {
                $this->logger->warning('configuration为空，跳过同步', [
                    'config_id' => $providerConfigEntity->getId(),
                ]);
                return;
            }

            $url = $config->getUrl();
            $apiKey = $config->getApiKey();
            if (! $url || ! $apiKey) {
                $this->logger->warning('configuration不完整，缺少url或api_key', [
                    'config_id' => $providerConfigEntity->getId(),
                    'has_url' => ! empty($url),
                    'has_api_key' => ! empty($apiKey),
                ]);
                return;
            }

            // 4. 根据category确定typeparameter
            $types = $this->getModelTypesByCategory($provider->getCategory());

            // 5. 从外部API拉取model
            $models = $this->fetchModelsFromApi($url, $apiKey, $types, $language);

            if (empty($models)) {
                $this->logger->warning('未从外部APIget到model', [
                    'config_id' => $providerConfigEntity->getId(),
                    'url' => $url,
                ]);
                return;
            }

            // 6. 同步model到database
            $this->syncModelsToDatabase($dataIsolation, $providerConfigEntity, $models, $language);

            $this->logger->info('从外部API同步model完成', [
                'config_id' => $providerConfigEntity->getId(),
                'model_count' => count($models),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('从外部API同步modelfail', [
                'config_id' => $providerConfigEntity->getId(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * 根据service商category确定要拉取的modeltype.
     */
    private function getModelTypesByCategory(Category $category): array
    {
        return match ($category) {
            Category::LLM => ['chat', 'embedding'],
            Category::VLM => ['image'],
            default => [],
        };
    }

    /**
     * 从外部API拉取model.
     */
    private function fetchModelsFromApi(string $url, string $apiKey, array $types, string $language): array
    {
        // getAPI地址
        $apiUrl = $this->buildModelsApiUrl($url);

        $allModels = [];

        // 为每个typecallAPI
        foreach ($types as $type) {
            try {
                $models = retry(3, function () use ($apiUrl, $apiKey, $type, $language) {
                    return $this->callModelsApi($apiUrl, $apiKey, $type, $language);
                }, 500);
                $allModels = array_merge($allModels, $models);
            } catch (Throwable $e) {
                $this->logger->error("拉取{$type}typemodelfail", [
                    'type' => $type,
                    'api_url' => $apiUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $allModels;
    }

    /**
     * call外部APIgetmodellist.
     */
    private function callModelsApi(string $apiUrl, string $apiKey, string $type, string $language): array
    {
        $client = $this->clientFactory->create([
            'timeout' => 30,
            'verify' => false,
        ]);

        $response = $client->get($apiUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'language' => $language ?: 'zh_CN',
            ],
            'query' => [
                'with_info' => 1,
                'type' => $type,
            ],
        ]);

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        if (! isset($data['data']) || ! is_array($data['data'])) {
            $this->logger->warning('APIreturn格式error', [
                'api_url' => $apiUrl,
                'type' => $type,
                'response' => $body,
            ]);
            return [];
        }

        $this->logger->info('success从API拉取model', [
            'api_url' => $apiUrl,
            'type' => $type,
            'model_count' => count($data['data']),
        ]);

        return $data['data'];
    }

    /**
     * 将model同步到database.
     */
    private function syncModelsToDatabase(
        ProviderDataIsolation $dataIsolation,
        ProviderConfigEntity $providerConfigEntity,
        array $models,
        string $language
    ): void {
        $configId = $providerConfigEntity->getId();

        // get现有的所有model
        $existingModels = $this->providerModelDomainService->getByProviderConfigId($dataIsolation, (string) $configId);

        // 建立model_id -> entity的映射
        $existingModelMap = [];
        foreach ($existingModels as $model) {
            $existingModelMap[$model->getModelId()] = $model;
        }

        // 提取新model的model_id
        $newModelIds = array_column($models, 'id');

        // 遍历新model，create或update
        foreach ($models as $modelData) {
            $modelId = $modelData['id'] ?? null;
            if (! $modelId) {
                continue;
            }

            try {
                if (isset($existingModelMap[$modelId])) {
                    // update现有model
                    $this->updateModel($dataIsolation, $existingModelMap[$modelId], $modelData, $providerConfigEntity, $language);
                } else {
                    // create新model
                    $this->createModel($dataIsolation, $modelData, $providerConfigEntity, $language);
                }
            } catch (Throwable $e) {
                $this->logger->error('同步modelfail', [
                    'model_id' => $modelId,
                    'error' => $e->getMessage(),
                ]);
                // 继续处理其他model
            }
        }

        // delete不再存在的model
        foreach ($existingModelMap as $modelId => $existingModel) {
            if (! in_array($modelId, $newModelIds)) {
                try {
                    $this->providerModelDomainService->deleteById($dataIsolation, (string) $existingModel->getId());
                    $this->logger->info('delete过期model', [
                        'model_id' => $modelId,
                        'entity_id' => $existingModel->getId(),
                    ]);
                } catch (Throwable $e) {
                    $this->logger->error('deletemodelfail', [
                        'model_id' => $modelId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * create新model.
     */
    private function createModel(
        ProviderDataIsolation $dataIsolation,
        array $modelData,
        ProviderConfigEntity $providerConfigEntity,
        string $language
    ): void {
        $saveDTO = $this->modelToReqDTO($dataIsolation, $modelData, $providerConfigEntity, $language);

        // 保存model
        $this->providerModelDomainService->saveModel($dataIsolation, $saveDTO);

        $this->logger->info('create新model', [
            'model_id' => $modelData['id'],
            'name' => $saveDTO->getName(),
        ]);
    }

    /**
     * update现有model.
     */
    private function updateModel(
        ProviderDataIsolation $dataIsolation,
        ProviderModelEntity $existingModel,
        array $modelData,
        ProviderConfigEntity $providerConfigEntity,
        string $language
    ): void {
        $saveDTO = $this->modelToReqDTO($dataIsolation, $modelData, $providerConfigEntity, $language);

        $saveDTO->setId($existingModel->getId());
        $saveDTO->setStatus($existingModel->getStatus()); // 保持原有status

        // 保存model
        $this->providerModelDomainService->saveModel($dataIsolation, $saveDTO);

        $this->logger->debug('updatemodel', [
            'model_id' => $modelData['id'],
            'name' => $saveDTO->getName(),
        ]);
    }

    private function modelToReqDTO(
        ProviderDataIsolation $dataIsolation,
        array $modelData,
        ProviderConfigEntity $providerConfigEntity,
        string $language
    ): SaveProviderModelDTO {
        // 如果是一个链接，那么需要对 url 进行限制
        $iconUrl = $modelData['info']['attributes']['icon'] ?? '';
        try {
            $iconUrl = str_replace(' ', '%20', $iconUrl);
            if (EasyFileTools::isUrl($iconUrl)) {
                $iconUrl = SSRFUtil::getSafeUrl($iconUrl, replaceIp: false);
                $uploadFile = new UploadFile($iconUrl);
                $this->fileDomainService->uploadByCredential($dataIsolation->getCurrentOrganizationCode(), $uploadFile);
                $iconUrl = $uploadFile->getKey();
            }
        } catch (Throwable $e) {
            $this->logger->error('上传文件fail:' . $e->getMessage(), ['icon_url' => $iconUrl]);
        }

        $saveDTO = new SaveProviderModelDTO();
        $saveDTO->setIcon($iconUrl);
        $saveDTO->setServiceProviderConfigId($providerConfigEntity->getId());
        $saveDTO->setModelId($modelData['id']);
        $saveDTO->setModelVersion($modelData['id']);
        $saveDTO->setName($modelData['info']['attributes']['label'] ?? $modelData['id']);
        $saveDTO->setDescription($modelData['info']['attributes']['description'] ?? '');
        $saveDTO->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $saveDTO->setModelType($modelData['info']['attributes']['model_type']);
        $saveDTO->setTranslate([
            'description' => [
                $language => $saveDTO->getDescription(),
            ],
            'name' => [
                $language => $saveDTO->getName(),
            ],
        ]);
        $saveDTO->setConfig([
            'creativity' => $modelData['info']['options']['default_temperature'] ?? 0.5,
            'support_function' => $modelData['info']['options']['function_call'] ?? false,
            'support_multi_modal' => $modelData['info']['options']['multi_modal'] ?? false,
            'support_embedding' => $modelData['info']['options']['embedding'] ?? false,
            'max_tokens' => $modelData['info']['options']['max_tokens'] ?? 200000,
            'max_output_tokens' => $modelData['info']['options']['max_output_tokens'] ?? 8192,
            'support_deep_think' => false,
        ]);

        // setcategory
        $objectType = $modelData['object'] ?? 'model';
        $category = $objectType === 'image' ? Category::VLM : Category::LLM;
        $saveDTO->setCategory($category);
        return $saveDTO;
    }

    /**
     * 构建modelAPI链接.
     */
    private function buildModelsApiUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . DelightfulUriTool::getModelsUri();
    }
}
