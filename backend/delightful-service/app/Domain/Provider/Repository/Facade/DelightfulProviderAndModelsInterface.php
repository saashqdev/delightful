<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Facade;

use App\Domain\Provider\DTO\ProviderConfigDTO;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Status;

/**
 * organizationdown的 Delightful service商及model的相关interface（non官方organization才have Delightful service商）.
 */
interface DelightfulProviderAndModelsInterface
{
    /**
     * getorganizationdown的 Delightful service商configuration（not containmodeldetail）.
     */
    public function getDelightfulProvider(ProviderDataIsolation $dataIsolation, Category $category, ?Status $status = null): ?ProviderConfigDTO;

    /**
     * according toorganizationencoding和category别get Delightful service商modellist.
     *
     * @param string $organizationCode organizationencoding
     * @param null|Category $category service商category别，为空o clockreturn所havecategorymodel
     * @return array<ProviderModelEntity> Delightful service商model实bodyarray
     */
    public function getDelightfulEnableModels(string $organizationCode, ?Category $category = null): array;

    /**
     * according to modelParentId getorganization Delightful model.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param string $modelParentId model父ID
     * @return null|ProviderModelEntity 找to的organizationmodel实body，not存inthenreturnnull
     */
    public function getDelightfulModelByParentId(ProviderDataIsolation $dataIsolation, string $modelParentId): ?ProviderModelEntity;

    /**
     * according toIDgetorganization Delightful model.
     *
     * @param int $id modelID`
     * @return null|ProviderModelEntity 找to的model实body，not存inthenreturnnull
     */
    public function getDelightfulModelById(int $id): ?ProviderModelEntity;

    /**
     * non官方organizationupdate Delightful modelstatus（写o clock复制逻辑）.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param ProviderModelEntity $officialModel 官方model实body
     * @return string organizationmodelID
     */
    public function updateDelightfulModelStatus(
        ProviderDataIsolation $dataIsolation,
        ProviderModelEntity $officialModel
    ): string;
}
