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
     * handlegroupDTOarray中的图标，将pathconvert为完整的URL.
     *
     * @param ModeGroupDTO[] $groups
     */
    protected function processGroupIcons(array $groups): void
    {
        // 收集所haveneedhandle的iconpath
        $iconPaths = [];

        foreach ($groups as $group) {
            $groupIcon = $group->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon)) {
                $iconPaths[] = $groupIcon;
            }
        }

        // ifnothaveneedhandle的icon，直接return
        if (empty($iconPaths)) {
            return;
        }

        // 去重
        $iconPaths = array_unique($iconPaths);

        // 批量geticon的URL（自动按organizationcodegrouphandle）
        $iconUrls = $this->fileDomainService->getBatchLinksByOrgPaths($iconPaths);

        // 替换DTO中的iconpath为完整URL
        foreach ($groups as $group) {
            $groupIcon = $group->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon) && isset($iconUrls[$groupIcon])) {
                $group->setIcon($iconUrls[$groupIcon]->getUrl());
            }
        }
    }

    /**
     * handle模式aggregate根中的图标，将pathconvert为完整的URL.
     */
    protected function processModeAggregateIcons(AdminModeAggregateDTO|ModeAggregate|ModeAggregateDTO $modeAggregateDTO): void
    {
        // 收集所haveneedhandle的iconpath
        $iconPaths = [];

        // 收集group的iconpath
        foreach ($modeAggregateDTO->getGroups() as $groupAggregate) {
            $groupIcon = $groupAggregate->getGroup()->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon)) {
                $iconPaths[] = $groupIcon;
            }

            // 收集model的iconpath
            foreach ($groupAggregate->getModels() as $model) {
                $modelIcon = $model->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon)) {
                    $iconPaths[] = $modelIcon;
                }
            }

            // 收集图像model的iconpath
            foreach ($groupAggregate->getImageModels() as $imageModel) {
                $modelIcon = $imageModel->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon)) {
                    $iconPaths[] = $modelIcon;
                }
            }
        }

        // ifnothaveneedhandle的icon，直接return
        if (empty($iconPaths)) {
            return;
        }

        // 去重
        $iconPaths = array_unique($iconPaths);

        // 批量geticon的URL（自动按organizationcodegrouphandle）
        $iconUrls = $this->fileDomainService->getBatchLinksByOrgPaths($iconPaths);

        // 替换DTO中的iconpath为完整URL
        foreach ($modeAggregateDTO->getGroups() as $groupAggregate) {
            $group = $groupAggregate->getGroup();
            $groupIcon = $group->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon) && isset($iconUrls[$groupIcon])) {
                $group->setIcon($iconUrls[$groupIcon]->getUrl());
            }

            // 替换model的icon
            foreach ($groupAggregate->getModels() as $model) {
                $modelIcon = $model->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon) && isset($iconUrls[$modelIcon])) {
                    $model->setModelIcon($iconUrls[$modelIcon]->getUrl());
                }
            }

            // 替换图像model的icon
            foreach ($groupAggregate->getImageModels() as $imageModel) {
                $modelIcon = $imageModel->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon) && isset($iconUrls[$modelIcon])) {
                    $imageModel->setModelIcon($iconUrls[$modelIcon]->getUrl());
                }
            }
        }
    }

    /**
     * getdata隔离object
     */
    protected function getModeDataIsolation(DelightfulUserAuthorization $authorization): ModeDataIsolation
    {
        return $this->createModeDataIsolation($authorization);
    }

    /**
     * handleModeGroupDetailDTOarray中的图标，将pathconvert为完整的URL.
     *
     * @param ModeGroupDetailDTO[] $modeGroupDetails
     */
    protected function processModeGroupDetailIcons(DelightfulUserAuthorization $authorization, array $modeGroupDetails): void
    {
        // 收集所haveneedhandle的iconpath
        $iconPaths = [];

        foreach ($modeGroupDetails as $groupDetail) {
            // 收集group的iconpath
            $groupIcon = $groupDetail->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon)) {
                $iconPaths[] = $groupIcon;
            }

            // 收集model的iconpath
            foreach ($groupDetail->getModels() as $model) {
                $modelIcon = $model->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon)) {
                    $iconPaths[] = $modelIcon;
                }
            }
        }

        // ifnothaveneedhandle的icon，直接return
        if (empty($iconPaths)) {
            return;
        }

        // 去重
        $iconPaths = array_unique($iconPaths);

        // 批量geticon的URL（自动按organizationcodegrouphandle）
        $iconUrls = $this->fileDomainService->getBatchLinksByOrgPaths($iconPaths);

        // 替换DTO中的iconpath为完整URL
        foreach ($modeGroupDetails as $groupDetail) {
            // 替换group的icon
            $groupIcon = $groupDetail->getIcon();
            if (! empty($groupIcon) && ! is_url($groupIcon) && isset($iconUrls[$groupIcon])) {
                $groupDetail->setIcon($iconUrls[$groupIcon]->getUrl());
            }

            // 替换model的icon
            foreach ($groupDetail->getModels() as $model) {
                $modelIcon = $model->getModelIcon();
                if (! empty($modelIcon) && ! is_url($modelIcon) && isset($iconUrls[$modelIcon])) {
                    $model->setModelIcon($iconUrls[$modelIcon]->getUrl());
                }
            }
        }
    }

    /**
     * getmodel（考虑service商级联status）.
     * @return ProviderModelEntity[]
     */
    protected function getModels(ModeAggregate $modeAggregate): array
    {
        // get所havemodelID (usemodel_id而not是provider_model_id)
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

        // 批量getmodel
        $allModels = $this->providerModelDomainService->getModelsByModelIds($providerDataIsolation, array_unique($allModelIds));

        // 提取所haveservice商ID
        $providerConfigIds = [];
        foreach ($allModels as $models) {
            foreach ($models as $model) {
                $providerConfigIds[] = $model->getServiceProviderConfigId();
            }
        }

        // 批量getservice商status
        $providerStatuses = [];
        if (! empty($providerConfigIds)) {
            $providerConfigs = $this->providerConfigDomainService->getByIds($providerDataIsolation, array_unique($providerConfigIds));
            foreach ($providerConfigs as $config) {
                $providerStatuses[$config->getId()] = $config->getStatus();
            }
        }

        // 为each个model_id选择most佳model（考虑级联status）
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
     * get详细的modelinfo（useat管理后台，考虑service商级联status）.
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

        // 单次queryget完整的modelinfo
        $allModels = $this->providerModelDomainService->getModelsByModelIds($providerDataIsolation, array_unique($allModelIds));

        // 提取所haveservice商ID
        $providerConfigIds = [];
        foreach ($allModels as $models) {
            foreach ($models as $model) {
                $providerConfigIds[] = $model->getServiceProviderConfigId();
            }
        }

        // 批量getservice商status
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
     * frommodel列表中选择most佳model（考虑service商级联status）.
     *
     * @param ProviderModelEntity[] $models model列表
     * @param array<int, Status> $providerStatuses service商statusmapping
     * @return null|ProviderModelEntity 选择的most佳model，ifnothave可usemodelthenreturnnull
     */
    private function selectBestModel(array $models, array $providerStatuses = []): ?ProviderModelEntity
    {
        if (empty($models)) {
            return null;
        }

        // ifnothave提供service商status，use原have逻辑（to后compatible）
        if (empty($providerStatuses)) {
            foreach ($models as $model) {
                if ($model->getStatus() && $model->getStatus()->value === Status::Enabled->value) {
                    return $model;
                }
            }
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
     * according tomodel列表确定status（考虑service商级联status）.
     *
     * @param ProviderModelEntity[] $models model列表
     * @param array<int, Status> $providerStatuses service商statusmapping
     * @return ModelStatus status：Normal、Disabled、Deleted
     */
    private function determineStatus(array $models, array $providerStatuses = []): ModelStatus
    {
        if (empty($models)) {
            return ModelStatus::Deleted;
        }

        // ifnothave提供service商status，use原have逻辑（to后compatible）
        if (empty($providerStatuses)) {
            foreach ($models as $model) {
                if ($model->getStatus() && $model->getStatus() === Status::Enabled) {
                    return ModelStatus::Normal;
                }
            }
            return ModelStatus::Disabled;
        }

        // 级联status判断
        foreach ($models as $model) {
            $providerId = $model->getServiceProviderConfigId();
            $providerStatus = $providerStatuses[$providerId] ?? Status::Disabled;

            // service商enableandmodelenable才算正常
            if ($providerStatus === Status::Enabled && $model->getStatus() && $model->getStatus()->value === Status::Enabled->value) {
                return ModelStatus::Normal;
            }
        }

        return ModelStatus::Disabled;
    }
}
