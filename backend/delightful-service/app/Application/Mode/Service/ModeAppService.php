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

        // get目front所havecanuse agent
        $beDelightfulAgentAppService = di(BeDelightfulAgentAppService::class);
        $agentData = $beDelightfulAgentAppService->queries($authorization, new BeDelightfulAgentQuery(), Page::createNoPage());
        // merge常useandall部 agent list,常useinfront
        /** @var array<BeDelightfulAgentEntity> $allAgents */
        $allAgents = array_merge($agentData['frequent'], $agentData['all']);
        if (empty($allAgents)) {
            return [];
        }

        // getback台所have模type,useatencapsulationdatato Agent middle
        $query = new ModeQuery(status: true);
        $modeEnabledList = $this->modeDomainService->getModes($modeDataIsolation, $query, Page::createNoPage())['list'];

        // batchquantitybuild模typeaggregateroot
        $modeAggregates = $this->modeDomainService->batchBuildModeAggregates($modeDataIsolation, $modeEnabledList);

        // ===== performanceoptimize:batchquantity预query =====

        // step1:预收collection所haveneedmodelId
        $allModelIds = [];
        foreach ($modeAggregates as $aggregate) {
            foreach ($aggregate->getGroupAggregates() as $groupAggregate) {
                foreach ($groupAggregate->getRelations() as $relation) {
                    $allModelIds[] = $relation->getModelId();
                }
            }
        }

        // step2:batchquantityquery所havemodelandservicequotientstatus
        $allProviderModelsWithStatus = $this->getModelsBatch(array_unique($allModelIds));

        // step3:organizationmodelfilter

        // first收collection所haveneedfiltermodel(LLM)
        $allAggregateModels = [];
        foreach ($modeAggregates as $aggregate) {
            $aggregateModels = $this->getModelsForAggregate($aggregate, $allProviderModelsWithStatus);
            $allAggregateModels = array_merge($allAggregateModels, $aggregateModels);
        }

        // 收collection所haveneedfiltergraphlikemodel(VLM)
        $allAggregateImageModels = [];
        foreach ($modeAggregates as $aggregate) {
            $aggregateImageModels = $this->getImageModelsForAggregate($aggregate, $allProviderModelsWithStatus);
            $allAggregateImageModels = array_merge($allAggregateImageModels, $aggregateImageModels);
        }

        // need升levelset餐
        $upgradeRequiredModelIds = [];

        // useorganizationfilter器conductfilter(LLM)
        if ($this->organizationModelFilter) {
            $providerModels = $this->organizationModelFilter->filterModelsByOrganization(
                $authorization->getOrganizationCode(),
                $allAggregateModels
            );
            $upgradeRequiredModelIds = $this->organizationModelFilter->getUpgradeRequiredModelIds($authorization->getOrganizationCode());
        } else {
            // ifnothaveorganizationfilter器,return所havemodel(open源versionlinefor)
            $providerModels = $allAggregateModels;
        }

        // useorganizationfilter器conductfilter(VLM)
        if ($this->organizationModelFilter) {
            $providerImageModels = $this->organizationModelFilter->filterModelsByOrganization(
                $authorization->getOrganizationCode(),
                $allAggregateImageModels
            );
        } else {
            // ifnothaveorganizationfilter器,return所havemodel(open源versionlinefor)
            $providerImageModels = $allAggregateImageModels;
        }

        // convertforDTOarray
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
                // usedefault
                $modeAggregateDTO = $modeAggregateDTOs['default'] ?? null;
            }
            if (! $modeAggregateDTO) {
                continue;
            }
            // ifnothaveconfigurationanymodel,wantbefilter
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

        // processgraph标pathconvertforcompleteURL
        $this->processModeGroupDetailIcons($authorization, $modeGroupDetailDTOS);

        return $modeGroupDetailDTOS;
    }

    /**
     * batchquantitygetmodelandservicequotientstatus(performanceoptimizeversion).
     * @param array $allModelIds 所haveneedquerymodelId
     * @return array<string, ProviderModelEntity> alreadypasslevel联statusfiltercanusemodel
     */
    private function getModelsBatch(array $allModelIds): array
    {
        if (empty($allModelIds)) {
            return [];
        }

        $providerDataIsolation = new ProviderDataIsolation(OfficialOrganizationUtil::getOfficialOrganizationCode());

        // batchquantitygetmodel
        $allModels = $this->providerModelDomainService->getModelsByModelIds($providerDataIsolation, $allModelIds);

        // extract所haveservicequotientID
        $providerConfigIds = [];
        foreach ($allModels as $models) {
            foreach ($models as $model) {
                $providerConfigIds[] = $model->getServiceProviderConfigId();
            }
        }

        // batchquantitygetservicequotientstatus(the2timeSQLquery)
        $providerStatuses = [];
        if (! empty($providerConfigIds)) {
            $providerConfigs = $this->providerConfigDomainService->getByIds($providerDataIsolation, array_unique($providerConfigIds));
            foreach ($providerConfigs as $config) {
                $providerStatuses[$config->getId()] = $config->getStatus();
            }
        }

        // applicationlevel联statusfilter,returncanusemodel
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
     * forbatchquantityqueryoptimizemodelchoosemethod.
     * @param ProviderModelEntity[] $models modellist
     * @param array $providerStatuses servicequotientstatusmapping
     */
    private function selectBestModelForBatch(array $models, array $providerStatuses): ?ProviderModelEntity
    {
        if (empty($models)) {
            return null;
        }

        // 优先chooseservicequotientenableandmodelenablemodel
        foreach ($models as $model) {
            $providerId = $model->getServiceProviderConfigId();
            $providerStatus = $providerStatuses[$providerId] ?? Status::Disabled;

            // servicequotientdisable,skipthemodel
            if ($providerStatus === Status::Disabled) {
                continue;
            }

            // servicequotientenable,checkmodelstatus
            if ($model->getStatus() && $model->getStatus()->value === Status::Enabled->value) {
                return $model;
            }
        }

        return null;
    }

    /**
     * frombatchquantityqueryresultmiddleextract特定aggregaterootmodel(LLM).
     * @param ModeAggregate $aggregate 模typeaggregateroot
     * @param array<string, ProviderModelEntity> $allProviderModels batchquantityquery所havemodelresult
     * @return array<string, ProviderModelEntity> theaggregateroot相closemodel
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
     * frombatchquantityqueryresultmiddleextract特定aggregaterootgraphlikemodel(VLM).
     * @param ModeAggregate $aggregate 模typeaggregateroot
     * @param array<string, ProviderModelEntity> $allProviderModels batchquantityquery所havemodelresult
     * @return array<string, ProviderModelEntity> theaggregateroot相closegraphlikemodel
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
                // onlyreturn VLM typemodel
                if ($providerModel->getCategory() !== Category::VLM) {
                    continue;
                }
                $aggregateImageModels[$modelId] = $providerModel;
            }
        }

        return $aggregateImageModels;
    }
}
