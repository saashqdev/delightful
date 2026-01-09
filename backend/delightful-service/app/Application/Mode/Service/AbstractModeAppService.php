<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Mode\Service;

use App\Application\Kernel\AbstractKernelAppService;
use App\Application\Mode\DTO\Admin\AdminModeAggregateDTO;
use App\Application\Mode\DTO\ModeAggregateDTO;
use App\Application\Mode\DTO\ModeGroupDetailDTO;
use App\Application\Mode\DTO\ModeGroupDTO;
use App\Application\Mode\DTO\ValueObject\ModelStatus;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Mode\Entity\ModeAggregate;
use App\Domain\Mode\Entity\ModeDataIsolation;
use App\Domain\Mode\Service\ModeDomainService;
use App\Domain\Mode\Service\ModeGroupDomainService;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Status;
use App\Domain\Provider\Service\ModelFilter\OrganizationBasedModelFilterInterface;
use App\Domain\Provider\Service\ProviderConfigDomainService;
use App\Domain\Provider\Service\ProviderModelDomainService;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Psr\Log\LoggerInterface;

abstract class AbstractModeAppService extends AbstractKernelAppService
{
    public function __construct(
        protected ModeDomainService $modeDomainService,
        protected ProviderModelDomainService $providerModelDomainService,
        protected ModeGroupDomainService $groupDomainService,
        protected FileDomainService $fileDomainService,
        protected LoggerInterface $logger,
        protected ProviderConfigDomainService $providerConfigDomainService,
        protected ?OrganizationBasedModelFilterInterface $organizationModelFilter,
    ) {
    }

    /**
     * handlegroupDTOarraymiddlegraph标,willpathconvertforcompleteURL.
     *
     * @param ModeGroupDTO[] $groups
     */
    protected function processGroupIcons(array $groups): void
    {
        // 收collection所haveneedhandleiconpath
        $iconPaths = [];

        foreach ($groups as $group) {
            $groupIcon = $group->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon)) {
                $iconPaths[] = $groupIcon;
            }
        }

        // ifnothaveneedhandleicon,directlyreturn
        if (empty($iconPaths)) {
            return;
        }

        // go重
        $iconPaths = array_unique($iconPaths);

        // batchquantitygeticonURL(fromauto byorganizationcodegrouphandle)
        $iconUrls = $this->fileDomainService->getBatchLinksByOrgPaths($iconPaths);

        // replaceDTOmiddleiconpathforcompleteURL
        foreach ($groups as $group) {
            $groupIcon = $group->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon) && isset($iconUrls[$groupIcon])) {
                $group->setIcon($iconUrls[$groupIcon]->getUrl());
            }
        }
    }

    /**
     * handle模typeaggregaterootmiddlegraph标,willpathconvertforcompleteURL.
     */
    protected function processModeAggregateIcons(AdminModeAggregateDTO|ModeAggregate|ModeAggregateDTO $modeAggregateDTO): void
    {
        // 收collection所haveneedhandleiconpath
        $iconPaths = [];

        // 收collectiongroupiconpath
        foreach ($modeAggregateDTO->getGroups() as $groupAggregate) {
            $groupIcon = $groupAggregate->getGroup()->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon)) {
                $iconPaths[] = $groupIcon;
            }

            // 收collectionmodeliconpath
            foreach ($groupAggregate->getModels() as $model) {
                $modelIcon = $model->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon)) {
                    $iconPaths[] = $modelIcon;
                }
            }

            // 收collectiongraphlikemodeliconpath
            foreach ($groupAggregate->getImageModels() as $imageModel) {
                $modelIcon = $imageModel->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon)) {
                    $iconPaths[] = $modelIcon;
                }
            }
        }

        // ifnothaveneedhandleicon,directlyreturn
        if (empty($iconPaths)) {
            return;
        }

        // go重
        $iconPaths = array_unique($iconPaths);

        // batchquantitygeticonURL(fromauto byorganizationcodegrouphandle)
        $iconUrls = $this->fileDomainService->getBatchLinksByOrgPaths($iconPaths);

        // replaceDTOmiddleiconpathforcompleteURL
        foreach ($modeAggregateDTO->getGroups() as $groupAggregate) {
            $group = $groupAggregate->getGroup();
            $groupIcon = $group->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon) && isset($iconUrls[$groupIcon])) {
                $group->setIcon($iconUrls[$groupIcon]->getUrl());
            }

            // replacemodelicon
            foreach ($groupAggregate->getModels() as $model) {
                $modelIcon = $model->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon) && isset($iconUrls[$modelIcon])) {
                    $model->setModelIcon($iconUrls[$modelIcon]->getUrl());
                }
            }

            // replacegraphlikemodelicon
            foreach ($groupAggregate->getImageModels() as $imageModel) {
                $modelIcon = $imageModel->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon) && isset($iconUrls[$modelIcon])) {
                    $imageModel->setModelIcon($iconUrls[$modelIcon]->getUrl());
                }
            }
        }
    }

    /**
     * getdataisolationobject
     */
    protected function getModeDataIsolation(DelightfulUserAuthorization $authorization): ModeDataIsolation
    {
        return $this->createModeDataIsolation($authorization);
    }

    /**
     * handleModeGroupDetailDTOarraymiddlegraph标,willpathconvertforcompleteURL.
     *
     * @param ModeGroupDetailDTO[] $modeGroupDetails
     */
    protected function processModeGroupDetailIcons(DelightfulUserAuthorization $authorization, array $modeGroupDetails): void
    {
        // 收collection所haveneedhandleiconpath
        $iconPaths = [];

        foreach ($modeGroupDetails as $groupDetail) {
            // 收collectiongroupiconpath
            $groupIcon = $groupDetail->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon)) {
                $iconPaths[] = $groupIcon;
            }

            // 收collectionmodeliconpath
            foreach ($groupDetail->getModels() as $model) {
                $modelIcon = $model->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon)) {
                    $iconPaths[] = $modelIcon;
                }
            }
        }

        // ifnothaveneedhandleicon,directlyreturn
        if (empty($iconPaths)) {
            return;
        }

        // go重
        $iconPaths = array_unique($iconPaths);

        // batchquantitygeticonURL(fromauto byorganizationcodegrouphandle)
        $iconUrls = $this->fileDomainService->getBatchLinksByOrgPaths($iconPaths);

        // replaceDTOmiddleiconpathforcompleteURL
        foreach ($modeGroupDetails as $groupDetail) {
            // replacegroupicon
            $groupIcon = $groupDetail->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon) && isset($iconUrls[$groupIcon])) {
                $groupDetail->setIcon($iconUrls[$groupIcon]->getUrl());
            }

            // replacemodelicon
            foreach ($groupDetail->getModels() as $model) {
                $modelIcon = $model->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon) && isset($iconUrls[$modelIcon])) {
                    $model->setModelIcon($iconUrls[$modelIcon]->getUrl());
                }
            }
        }
    }

    /**
     * getmodel(considerservicequotientlevel联status).
     * @return ProviderModelEntity[]
     */
    protected function getModels(ModeAggregate $modeAggregate): array
    {
        // get所havemodelID (usemodel_idwhilenotisprovider_model_id)
        $allModelIds = [];
        foreach ($modeAggregate->getGroupAggregates() as $groupAggregate) {
            foreach ($groupAggregate->getRelations() as $relation) {
                $allModelIds[] = $relation->getModelId();
            }
        }

        if (empty($allModelIds)) {
            return [];
        }

        $providerDataIsolation = new ProviderDataIsolation(OfficialOrganizationUtil::getOfficialOrganizationCode());

        // batchquantitygetmodel
        $allModels = $this->providerModelDomainService->getModelsByModelIds($providerDataIsolation, array_unique($allModelIds));

        // extract所haveservicequotientID
        $providerConfigIds = [];
        foreach ($allModels as $models) {
            foreach ($models as $model) {
                $providerConfigIds[] = $model->getServiceProviderConfigId();
            }
        }

        // batchquantitygetservicequotientstatus
        $providerStatuses = [];
        if (! empty($providerConfigIds)) {
            $providerConfigs = $this->providerConfigDomainService->getByIds($providerDataIsolation, array_unique($providerConfigIds));
            foreach ($providerConfigs as $config) {
                $providerStatuses[$config->getId()] = $config->getStatus();
            }
        }

        // foreachmodel_idchoosemost佳model(considerlevel联status)
        $providerModels = [];
        foreach ($allModels as $modelId => $models) {
            $bestModel = $this->selectBestModel($models, $providerStatuses);
            if ($bestModel) {
                $providerModels[$modelId] = $bestModel;
            }
        }

        return $providerModels;
    }

    /**
     * getdetailedmodelinfo(useatmanageback台,considerservicequotientlevel联status).
     * @return array<string, array{best: null|ProviderModelEntity, all: ProviderModelEntity[], status: ModelStatus}>
     */
    protected function getDetailedModels(ModeAggregate $modeAggregate): array
    {
        // get所havemodelID
        $allModelIds = [];
        foreach ($modeAggregate->getGroupAggregates() as $groupAggregate) {
            foreach ($groupAggregate->getRelations() as $relation) {
                $allModelIds[] = $relation->getModelId();
            }
        }

        if (empty($allModelIds)) {
            return [];
        }

        $providerDataIsolation = new ProviderDataIsolation(OfficialOrganizationUtil::getOfficialOrganizationCode());

        // singletimequerygetcompletemodelinfo
        $allModels = $this->providerModelDomainService->getModelsByModelIds($providerDataIsolation, array_unique($allModelIds));

        // extract所haveservicequotientID
        $providerConfigIds = [];
        foreach ($allModels as $models) {
            foreach ($models as $model) {
                $providerConfigIds[] = $model->getServiceProviderConfigId();
            }
        }

        // batchquantitygetservicequotientstatus
        $providerStatuses = [];
        if (! empty($providerConfigIds)) {
            $providerConfigs = $this->providerConfigDomainService->getByIds($providerDataIsolation, array_unique($providerConfigIds));
            foreach ($providerConfigs as $config) {
                $providerStatuses[$config->getId()] = $config->getStatus();
            }
        }

        $result = [];
        foreach (array_unique($allModelIds) as $modelId) {
            $models = $allModels[$modelId] ?? [];
            $bestModel = $this->selectBestModel($models, $providerStatuses);
            $status = $this->determineStatus($models, $providerStatuses);

            $result[$modelId] = [
                'best' => $bestModel,
                'all' => $models,
                'status' => $status,
            ];
        }

        return $result;
    }

    /**
     * frommodelcolumntablemiddlechoosemost佳model(considerservicequotientlevel联status).
     *
     * @param ProviderModelEntity[] $models modelcolumntable
     * @param array<int, Status> $providerStatuses servicequotientstatusmapping
     * @return null|ProviderModelEntity choosemost佳model,ifnothavecanusemodelthenreturnnull
     */
    private function selectBestModel(array $models, array $providerStatuses = []): ?ProviderModelEntity
    {
        if (empty($models)) {
            return null;
        }

        // ifnothaveprovideservicequotientstatus,use原havelogic(tobackcompatible)
        if (empty($providerStatuses)) {
            foreach ($models as $model) {
                if ($model->getStatus() && $model->getStatus()->value === Status::Enabled->value) {
                    return $model;
                }
            }
            return null;
        }

        // prioritychooseservicequotientenableandmodelenablemodel
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
     * according tomodelcolumntablecertainstatus(considerservicequotientlevel联status).
     *
     * @param ProviderModelEntity[] $models modelcolumntable
     * @param array<int, Status> $providerStatuses servicequotientstatusmapping
     * @return ModelStatus status:Normal,Disabled,Deleted
     */
    private function determineStatus(array $models, array $providerStatuses = []): ModelStatus
    {
        if (empty($models)) {
            return ModelStatus::Deleted;
        }

        // ifnothaveprovideservicequotientstatus,use原havelogic(tobackcompatible)
        if (empty($providerStatuses)) {
            foreach ($models as $model) {
                if ($model->getStatus() && $model->getStatus() === Status::Enabled) {
                    return ModelStatus::Normal;
                }
            }
            return ModelStatus::Disabled;
        }

        // level联statusjudge
        foreach ($models as $model) {
            $providerId = $model->getServiceProviderConfigId();
            $providerStatus = $providerStatuses[$providerId] ?? Status::Disabled;

            // servicequotientenableandmodelenable才算normal
            if ($providerStatus === Status::Enabled && $model->getStatus() && $model->getStatus()->value === Status::Enabled->value) {
                return ModelStatus::Normal;
            }
        }

        return ModelStatus::Disabled;
    }
}
