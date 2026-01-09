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
 * organizationdown Delightful servicequotientandmodel相closeinterface(non官方organization才have Delightful servicequotient).
 */
interface DelightfulProviderAndModelsInterface
{
    /**
     * getorganizationdown Delightful servicequotientconfiguration(not containmodeldetail).
     */
    public function getDelightfulProvider(ProviderDataIsolation $dataIsolation, Category $category, ?Status $status = null): ?ProviderConfigDTO;

    /**
     * according toorganizationencodingandcategory别get Delightful servicequotientmodellist.
     *
     * @param string $organizationCode organizationencoding
     * @param null|Category $category servicequotientcategory别,foremptyo clockreturn所havecategorymodel
     * @return array<ProviderModelEntity> Delightful servicequotientmodel实bodyarray
     */
    public function getDelightfulEnableModels(string $organizationCode, ?Category $category = null): array;

    /**
     * according to modelParentId getorganization Delightful model.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param string $modelParentId model父ID
     * @return null|ProviderModelEntity 找toorganizationmodel实body,not存inthenreturnnull
     */
    public function getDelightfulModelByParentId(ProviderDataIsolation $dataIsolation, string $modelParentId): ?ProviderModelEntity;

    /**
     * according toIDgetorganization Delightful model.
     *
     * @param int $id modelID`
     * @return null|ProviderModelEntity 找tomodel实body,not存inthenreturnnull
     */
    public function getDelightfulModelById(int $id): ?ProviderModelEntity;

    /**
     * non官方organizationupdate Delightful modelstatus(写o clockcopylogic).
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
