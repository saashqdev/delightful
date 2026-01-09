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

        // get目front的所have可use的 agent
        $beDelightfulAgentAppService = di(BeDelightfulAgentAppService::class);
        $agentData = $beDelightfulAgentAppService->queries($authorization, new BeDelightfulAgentQuery(), Page::createNoPage());
        // merge常use和all部 agent list，常useinfront
        /** @var array<BeDelightfulAgentEntity> $allAgents */
        $allAgents = array_merge($agentData['frequent'], $agentData['all']);
        if (empty($allAgents)) {
            return [];
        }

        // getback台的所have模type，useat封装datato Agent middle
        $query = new ModeQuery(status: true);
        $modeEnabledList = $this->modeDomainService->getModes($modeDataIsolation, $query, Page::createNoPage())['list'];

        // 批quantitybuild模type聚合root
        $modeAggregates = $this->modeDomainService->batchBuildModeAggregates($modeDataIsolation, $modeEnabledList);

        // ===== performanceoptimize：批quantity预query =====

        // 步骤1：预收集所haveneed的modelId
        $allModelIds = [];
        foreach ($modeAggregates as $aggregate) {
            foreach ($aggregate->getGroupAggregates() as $groupAggregate) {
                foreach ($groupAggregate->getRelations() as $relation) {
                    $allModelIds[] = $relation->getModelId();
                }
            }
        }

        // 步骤2：批quantityquery所havemodel和service商status
        $allProviderModelsWithStatus = $this->getModelsBatch(array_unique($allModelIds));

        // 步骤3：organizationmodelfilter

        // 首先收集所haveneedfilter的model（LLM）
        $allAggregateModels = [];
        foreach ($modeAggregates as $aggregate) {
            $aggregateModels = $this->getModelsForAggregate($aggregate, $allProviderModelsWithStatus);
            $allAggregateModels = array_merge($allAggregateModels, $aggregateModels);
        }

        // 收集所haveneedfilter的graph像model（VLM）
        $allAggregateImageModels = [];
        foreach ($modeAggregates as $aggregate) {
            $aggregateImageModels = $this->getImageModelsForAggregate($aggregate, $allProviderModelsWithStatus);
            $allAggregateImageModels = array_merge($allAggregateImageModels, $aggregateImageModels);
        }

        // need升levelset餐
        $upgradeRequiredModelIds = [];

        // useorganizationfilter器conductfilter（LLM）
        if ($this->organizationModelFilter) {
            $providerModels = $this->organizationModelFilter->filterModelsByOrganization(
                $authorization->getOrganizationCode(),
                $allAggregateModels
            );
            $upgradeRequiredModelIds = $this->organizationModelFilter->getUpgradeRequiredModelIds($authorization->getOrganizationCode());
        } else {
            // ifnothaveorganizationfilter器，return所havemodel（开源versionline为）
            $providerModels = $allAggregateModels;
        }

        // useorganizationfilter器conductfilter（VLM）
        if ($this->organizationModelFilter) {
            $providerImageModels = $this->organizationModelFilter->filterModelsByOrganization(
                $authorization->getOrganizationCode(),
                $allAggregateImageModels
            );
        } else {
            // ifnothaveorganizationfilter器，return所havemodel（开源versionline为）
            $providerImageModels = $allAggregateImageModels;
        }

        // convert为DTOarray
        $modeAggregateDTOs = [];
        foreach ($modeAggregates as $aggregate) {
            $modeAggregateDTOs[$aggregate->getMode()->getIdentifier()] = ModeAssembler::aggregateToDTO($aggregate, $providerModels, $upgradeRequiredModelIds, $providerImageModels);
        }

        // processgraph标URLconvert
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
            // ifnothaveconfiguration任何model，要befilter
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

        // processgraph标pathconvert为完整URL
        $this->processModeGroupDetailIcons($authorization, $modeGroupDetailDTOS);

        return $modeGroupDetailDTOS;
    }

    /**
     * 批quantitygetmodel和service商status（performanceoptimizeversion）.
     * @param array $allModelIds 所haveneedquery的modelId
     * @return array<string, ProviderModelEntity> 已passlevel联statusfilter的可usemodel
     */
    private function getModelsBatch(array $allModelIds): array
    {
        if (empty($allModelIds)) {
            return [];
        }

        $providerDataIsolation = new ProviderDataIsolation(OfficialOrganizationUtil::getOfficialOrganizationCode());

        // 批quantitygetmodel
        $allModels = $this->providerModelDomainService->getModelsByModelIds($providerDataIsolation, $allModelIds);

        // 提取所haveservice商ID
        $providerConfigIds = [];
        foreach ($allModels as $models) {
            foreach ($models as $model) {
                $providerConfigIds[] = $model->getServiceProviderConfigId();
            }
        }

        // 批quantitygetservice商status（the2timeSQLquery）
        $providerStatuses = [];
        if (! empty($providerConfigIds)) {
            $providerConfigs = $this->providerConfigDomainService->getByIds($providerDataIsolation, array_unique($providerConfigIds));
            foreach ($providerConfigs as $config) {
                $providerStatuses[$config->getId()] = $config->getStatus();
            }
        }

        // applicationlevel联statusfilter，return可usemodel
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
     * 为批quantityqueryoptimize的model选择method.
     * @param ProviderModelEntity[] $models modellist
     * @param array $providerStatuses service商statusmapping
     */
    private function selectBestModelForBatch(array $models, array $providerStatuses): ?ProviderModelEntity
    {
        if (empty($models)) {
            return null;
        }

        // 优先选择service商enableandmodelenable的model
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
     * from批quantityqueryresultmiddle提取特定聚合root的model（LLM）.
     * @param ModeAggregate $aggregate 模type聚合root
     * @param array<string, ProviderModelEntity> $allProviderModels 批quantityquery的所havemodelresult
     * @return array<string, ProviderModelEntity> 该聚合root相关的model
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
     * from批quantityqueryresultmiddle提取特定聚合root的graph像model（VLM）.
     * @param ModeAggregate $aggregate 模type聚合root
     * @param array<string, ProviderModelEntity> $allProviderModels 批quantityquery的所havemodelresult
     * @return array<string, ProviderModelEntity> 该聚合root相关的graph像model
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
