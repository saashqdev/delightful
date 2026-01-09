<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Mode\Service;

use App\Application\Mode\Assembler\ModeAssembler;
use App\Application\Mode\DTO\ModeGroupDetailDTO;
use App\Domain\Mode\Entity\ModeAggregate;
use App\Domain\Mode\Entity\ValueQuery\ModeQuery;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Application\Agent\Service\BeDelightfulAgentAppService;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\Query\BeDelightfulAgentQuery;

class ModeAppService extends AbstractModeAppService
{
    public function getModes(DelightfulUserAuthorization $authorization): array
    {
        $modeDataIsolation = $this->getModeDataIsolation($authorization);
        $modeDataIsolation->disabled();

        // get目前的所有可用的 agent
        $beDelightfulAgentAppService = di(BeDelightfulAgentAppService::class);
        $agentData = $beDelightfulAgentAppService->queries($authorization, new BeDelightfulAgentQuery(), Page::createNoPage());
        // merge常用和全部 agent list，常用在前
        /** @var array<BeDelightfulAgentEntity> $allAgents */
        $allAgents = array_merge($agentData['frequent'], $agentData['all']);
        if (empty($allAgents)) {
            return [];
        }

        // get后台的所有模式，用于封装data到 Agent 中
        $query = new ModeQuery(status: true);
        $modeEnabledList = $this->modeDomainService->getModes($modeDataIsolation, $query, Page::createNoPage())['list'];

        // 批量build模式聚合根
        $modeAggregates = $this->modeDomainService->batchBuildModeAggregates($modeDataIsolation, $modeEnabledList);

        // ===== performanceoptimize：批量预query =====

        // 步骤1：预收集所有need的modelId
        $allModelIds = [];
        foreach ($modeAggregates as $aggregate) {
            foreach ($aggregate->getGroupAggregates() as $groupAggregate) {
                foreach ($groupAggregate->getRelations() as $relation) {
                    $allModelIds[] = $relation->getModelId();
                }
            }
        }

        // 步骤2：批量query所有model和service商status
        $allProviderModelsWithStatus = $this->getModelsBatch(array_unique($allModelIds));

        // 步骤3：organizationmodelfilter

        // 首先收集所有needfilter的model（LLM）
        $allAggregateModels = [];
        foreach ($modeAggregates as $aggregate) {
            $aggregateModels = $this->getModelsForAggregate($aggregate, $allProviderModelsWithStatus);
            $allAggregateModels = array_merge($allAggregateModels, $aggregateModels);
        }

        // 收集所有needfilter的图像model（VLM）
        $allAggregateImageModels = [];
        foreach ($modeAggregates as $aggregate) {
            $aggregateImageModels = $this->getImageModelsForAggregate($aggregate, $allProviderModelsWithStatus);
            $allAggregateImageModels = array_merge($allAggregateImageModels, $aggregateImageModels);
        }

        // need升级套餐
        $upgradeRequiredModelIds = [];

        // useorganizationfilter器进行filter（LLM）
        if ($this->organizationModelFilter) {
            $providerModels = $this->organizationModelFilter->filterModelsByOrganization(
                $authorization->getOrganizationCode(),
                $allAggregateModels
            );
            $upgradeRequiredModelIds = $this->organizationModelFilter->getUpgradeRequiredModelIds($authorization->getOrganizationCode());
        } else {
            // 如果没有organizationfilter器，return所有model（开源version行为）
            $providerModels = $allAggregateModels;
        }

        // useorganizationfilter器进行filter（VLM）
        if ($this->organizationModelFilter) {
            $providerImageModels = $this->organizationModelFilter->filterModelsByOrganization(
                $authorization->getOrganizationCode(),
                $allAggregateImageModels
            );
        } else {
            // 如果没有organizationfilter器，return所有model（开源version行为）
            $providerImageModels = $allAggregateImageModels;
        }

        // convert为DTOarray
        $modeAggregateDTOs = [];
        foreach ($modeAggregates as $aggregate) {
            $modeAggregateDTOs[$aggregate->getMode()->getIdentifier()] = ModeAssembler::aggregateToDTO($aggregate, $providerModels, $upgradeRequiredModelIds, $providerImageModels);
        }

        // process图标URLconvert
        foreach ($modeAggregateDTOs as $aggregateDTO) {
            $this->processModeAggregateIcons($aggregateDTO);
        }

        $list = [];
        foreach ($allAgents as $agent) {
            $modeAggregateDTO = $modeAggregateDTOs[$agent->getCode()] ?? null;
            if (! $modeAggregateDTO) {
                // usedefault的
                $modeAggregateDTO = $modeAggregateDTOs['default'] ?? null;
            }
            if (! $modeAggregateDTO) {
                continue;
            }
            // 如果没有configuration任何model，要被filter
            if (empty($modeAggregateDTO->getAllModelIds())) {
                continue;
            }
            // convert
            $list[] = [
                'mode' => [
                    'id' => $agent->getCode(),
                    'name' => $agent->getName(),
                    'placeholder' => $agent->getDescription(),
                    'identifier' => $agent->getCode(),
                    'icon_type' => $agent->getIconType(),
                    'icon_url' => $agent->getIcon()['url'] ?? '',
                    'icon' => $agent->getIcon()['type'] ?? '',
                    'color' => $agent->getIcon()['color'] ?? '',
                    'sort' => 0,
                ],
                'agent' => [
                    'type' => $agent->getType()->value,
                    'category' => $agent->getCategory(),
                ],
                'groups' => $modeAggregateDTO['groups'] ?? [],
            ];
        }

        return [
            'total' => count($list),
            'list' => $list,
        ];
    }

    /**
     * @return ModeGroupDetailDTO[]
     */
    public function getModeByIdentifier(DelightfulUserAuthorization $authorization, string $identifier): array
    {
        $modeDataIsolation = $this->getModeDataIsolation($authorization);
        $modeDataIsolation->disabled();
        $modeAggregate = $this->modeDomainService->getModeDetailByIdentifier($modeDataIsolation, $identifier);

        $providerModels = $this->getModels($modeAggregate);
        $modeGroupDetailDTOS = ModeAssembler::aggregateToFlatGroupsDTO($modeAggregate, $providerModels);

        // process图标pathconvert为完整URL
        $this->processModeGroupDetailIcons($authorization, $modeGroupDetailDTOS);

        return $modeGroupDetailDTOS;
    }

    /**
     * 批量getmodel和service商status（performanceoptimizeversion）.
     * @param array $allModelIds 所有needquery的modelId
     * @return array<string, ProviderModelEntity> 已pass级联statusfilter的可用model
     */
    private function getModelsBatch(array $allModelIds): array
    {
        if (empty($allModelIds)) {
            return [];
        }

        $providerDataIsolation = new ProviderDataIsolation(OfficialOrganizationUtil::getOfficialOrganizationCode());

        // 批量getmodel
        $allModels = $this->providerModelDomainService->getModelsByModelIds($providerDataIsolation, $allModelIds);

        // 提取所有service商ID
        $providerConfigIds = [];
        foreach ($allModels as $models) {
            foreach ($models as $model) {
                $providerConfigIds[] = $model->getServiceProviderConfigId();
            }
        }

        // 批量getservice商status（第2次SQLquery）
        $providerStatuses = [];
        if (! empty($providerConfigIds)) {
            $providerConfigs = $this->providerConfigDomainService->getByIds($providerDataIsolation, array_unique($providerConfigIds));
            foreach ($providerConfigs as $config) {
                $providerStatuses[$config->getId()] = $config->getStatus();
            }
        }

        // application级联statusfilter，return可用model
        $availableModels = [];
        foreach ($allModels as $modelId => $models) {
            $bestModel = $this->selectBestModelForBatch($models, $providerStatuses);
            if ($bestModel) {
                $availableModels[$modelId] = $bestModel;
            }
        }

        return $availableModels;
    }

    /**
     * 为批量queryoptimize的model选择method.
     * @param ProviderModelEntity[] $models modellist
     * @param array $providerStatuses service商statusmapping
     */
    private function selectBestModelForBatch(array $models, array $providerStatuses): ?ProviderModelEntity
    {
        if (empty($models)) {
            return null;
        }

        // 优先选择service商enable且modelenable的model
        foreach ($models as $model) {
            $providerId = $model->getServiceProviderConfigId();
            $providerStatus = $providerStatuses[$providerId] ?? Status::Disabled;

            // service商disable，skip该model
            if ($providerStatus === Status::Disabled) {
                continue;
            }

            // service商enable，checkmodelstatus
            if ($model->getStatus() && $model->getStatus()->value === Status::Enabled->value) {
                return $model;
            }
        }

        return null;
    }

    /**
     * 从批量queryresult中提取特定聚合根的model（LLM）.
     * @param ModeAggregate $aggregate 模式聚合根
     * @param array<string, ProviderModelEntity> $allProviderModels 批量query的所有modelresult
     * @return array<string, ProviderModelEntity> 该聚合根相关的model
     */
    private function getModelsForAggregate(ModeAggregate $aggregate, array $allProviderModels): array
    {
        $aggregateModels = [];

        foreach ($aggregate->getGroupAggregates() as $groupAggregate) {
            foreach ($groupAggregate->getRelations() as $relation) {
                $modelId = $relation->getModelId();

                if (! $providerModel = $allProviderModels[$modelId] ?? null) {
                    continue;
                }
                if (! $providerModel->getConfig()->isSupportFunction()) {
                    continue;
                }
                $aggregateModels[$modelId] = $providerModel;
            }
        }

        return $aggregateModels;
    }

    /**
     * 从批量queryresult中提取特定聚合根的图像model（VLM）.
     * @param ModeAggregate $aggregate 模式聚合根
     * @param array<string, ProviderModelEntity> $allProviderModels 批量query的所有modelresult
     * @return array<string, ProviderModelEntity> 该聚合根相关的图像model
     */
    private function getImageModelsForAggregate(ModeAggregate $aggregate, array $allProviderModels): array
    {
        $aggregateImageModels = [];

        foreach ($aggregate->getGroupAggregates() as $groupAggregate) {
            foreach ($groupAggregate->getRelations() as $relation) {
                $modelId = $relation->getModelId();

                if (! $providerModel = $allProviderModels[$modelId] ?? null) {
                    continue;
                }
                // 只return VLM type的model
                if ($providerModel->getCategory() !== Category::VLM) {
                    continue;
                }
                $aggregateImageModels[$modelId] = $providerModel;
            }
        }

        return $aggregateImageModels;
    }
}
