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
 * servicequotientmodelsyncapplicationservice.
 * responsiblefromoutside部APIpullmodelandsynctoOfficialservicequotient.
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
     * fromoutside部APIsyncmodel.
     * whenservicequotientconfigurationcreateorupdateo clock,ifisOfficialservicequotientandisofficialorganization,thenfromoutside部APIpullmodel.
     */
    public function syncModelsFromExternalApi(
        ProviderConfigEntity $providerConfigEntity,
        string $language,
        string $organizationCode
    ): void {
        // 1. checkwhetherforOfficialservicequotient
        $dataIsolation = ProviderDataIsolation::create($organizationCode);
        $provider = $this->providerConfigDomainService->getProviderById($dataIsolation, $providerConfigEntity->getServiceProviderId());

        if (! $provider || $provider->getProviderCode() !== ProviderCode::Official) {
            $this->logger->debug('notisOfficialservicequotient,skipsync', [
                'config_id' => $providerConfigEntity->getId(),
                'provider_code' => $provider?->getProviderCode()->value,
            ]);
            return;
        }

        $this->logger->info('startfromoutside部APIsyncmodel', [
            'config_id' => $providerConfigEntity->getId(),
            'organization_code' => $organizationCode,
            'provider_code' => $provider->getProviderCode()->value,
        ]);

        try {
            // 3. parseconfiguration
            $config = $providerConfigEntity->getConfig();
            if (! $config) {
                $this->logger->warning('configurationforempty,skipsync', [
                    'config_id' => $providerConfigEntity->getId(),
                ]);
                return;
            }

            $url = $config->getUrl();
            $apiKey = $config->getApiKey();
            if (! $url || ! $apiKey) {
                $this->logger->warning('configurationnotcomplete,missingurlorapi_key', [
                    'config_id' => $providerConfigEntity->getId(),
                    'has_url' => ! empty($url),
                    'has_api_key' => ! empty($apiKey),
                ]);
                return;
            }

            // 4. according tocategorycertaintypeparameter
            $types = $this->getModelTypesByCategory($provider->getCategory());

            // 5. fromoutside部APIpullmodel
            $models = $this->fetchModelsFromApi($url, $apiKey, $types, $language);

            if (empty($models)) {
                $this->logger->warning('notfromoutside部APIgettomodel', [
                    'config_id' => $providerConfigEntity->getId(),
                    'url' => $url,
                ]);
                return;
            }

            // 6. syncmodeltodatabase
            $this->syncModelsToDatabase($dataIsolation, $providerConfigEntity, $models, $language);

            $this->logger->info('fromoutside部APIsyncmodelcomplete', [
                'config_id' => $providerConfigEntity->getId(),
                'model_count' => count($models),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('fromoutside部APIsyncmodelfail', [
                'config_id' => $providerConfigEntity->getId(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    /**
     * according toservicequotientcategorycertainwantpullmodeltype.
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
     * fromoutside部APIpullmodel.
     */
    private function fetchModelsFromApi(string $url, string $apiKey, array $types, string $language): array
    {
        // getAPIground址
        $apiUrl = $this->buildModelsApiUrl($url);

        $allModels = [];

        // foreachtypecallAPI
        foreach ($types as $type) {
            try {
                $models = retry(3, function () use ($apiUrl, $apiKey, $type, $language) {
                    return $this->callModelsApi($apiUrl, $apiKey, $type, $language);
                }, 500);
                $allModels = array_merge($allModels, $models);
            } catch (Throwable $e) {
                $this->logger->error("pull{$type}typemodelfail", [
                    'type' => $type,
                    'api_url' => $apiUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $allModels;
    }

    /**
     * calloutside部APIgetmodellist.
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
            $this->logger->warning('APIreturnformaterror', [
                'api_url' => $apiUrl,
                'type' => $type,
                'response' => $body,
            ]);
            return [];
        }

        $this->logger->info('successfromAPIpullmodel', [
            'api_url' => $apiUrl,
            'type' => $type,
            'model_count' => count($data['data']),
        ]);

        return $data['data'];
    }

    /**
     * willmodelsynctodatabase.
     */
    private function syncModelsToDatabase(
        ProviderDataIsolation $dataIsolation,
        ProviderConfigEntity $providerConfigEntity,
        array $models,
        string $language
    ): void {
        $configId = $providerConfigEntity->getId();

        // get现have所havemodel
        $existingModels = $this->providerModelDomainService->getByProviderConfigId($dataIsolation, (string) $configId);

        // establishmodel_id -> entitymapping
        $existingModelMap = [];
        foreach ($existingModels as $model) {
            $existingModelMap[$model->getModelId()] = $model;
        }

        // extractnewmodelmodel_id
        $newModelIds = array_column($models, 'id');

        // traversenewmodel,createorupdate
        foreach ($models as $modelData) {
            $modelId = $modelData['id'] ?? null;
            if (! $modelId) {
                continue;
            }

            try {
                if (isset($existingModelMap[$modelId])) {
                    // update现havemodel
                    $this->updateModel($dataIsolation, $existingModelMap[$modelId], $modelData, $providerConfigEntity, $language);
                } else {
                    // createnewmodel
                    $this->createModel($dataIsolation, $modelData, $providerConfigEntity, $language);
                }
            } catch (Throwable $e) {
                $this->logger->error('syncmodelfail', [
                    'model_id' => $modelId,
                    'error' => $e->getMessage(),
                ]);
                // continueprocessothermodel
            }
        }

        // deletenotagain存inmodel
        foreach ($existingModelMap as $modelId => $existingModel) {
            if (! in_array($modelId, $newModelIds)) {
                try {
                    $this->providerModelDomainService->deleteById($dataIsolation, (string) $existingModel->getId());
                    $this->logger->info('deleteexpiremodel', [
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
     * createnewmodel.
     */
    private function createModel(
        ProviderDataIsolation $dataIsolation,
        array $modelData,
        ProviderConfigEntity $providerConfigEntity,
        string $language
    ): void {
        $saveDTO = $this->modelToReqDTO($dataIsolation, $modelData, $providerConfigEntity, $language);

        // savemodel
        $this->providerModelDomainService->saveModel($dataIsolation, $saveDTO);

        $this->logger->info('createnewmodel', [
            'model_id' => $modelData['id'],
            'name' => $saveDTO->getName(),
        ]);
    }

    /**
     * update现havemodel.
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
        $saveDTO->setStatus($existingModel->getStatus()); // maintain原havestatus

        // savemodel
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
        // ifisonelink,that么needto url conductlimit
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
            $this->logger->error('uploadfilefail:' . $e->getMessage(), ['icon_url' => $iconUrl]);
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
     * buildmodelAPIlink.
     */
    private function buildModelsApiUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . DelightfulUriTool::getModelsUri();
    }
}
