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
 * organizationdown Delightful servicequotientandmodelrelatedcloseinterface(nonofficialorganizationonlyhave Delightful servicequotient).
 */
interface DelightfulProviderAndModelsInterface
{
    /**
     * getorganizationdown Delightful servicequotientconfiguration(not containmodeldetail).
     */
    public function getDelightfulProvider(ProviderDataIsolation $dataIsolation, Category $category, ?Status $status = null): ?ProviderConfigDTO;

    /**
     * according toorganizationencodingandcategoryotherget Delightful servicequotientmodellist.
     *
     * @param string $organizationCode organizationencoding
     * @param null|Category $category servicequotientcategoryother,foremptyo clockreturn havecategorymodel
     * @return array<ProviderModelEntity> Delightful servicequotientmodelactualbodyarray
     */
    public function getDelightfulEnableModels(string $organizationCode, ?Category $category = null): array;

    /**
     * according to modelParentId getorganization Delightful model.
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param string $modelParentId modelparentID
     * @return null|ProviderModelEntity findtoorganizationmodelactualbody,notexistsinthenreturnnull
     */
    public function getDelightfulModelByParentId(ProviderDataIsolation $dataIsolation, string $modelParentId): ?ProviderModelEntity;

    /**
     * according toIDgetorganization Delightful model.
     *
     * @param int $id modelID`
     * @return null|ProviderModelEntity findtomodelactualbody,notexistsinthenreturnnull
     */
    public function getDelightfulModelById(int $id): ?ProviderModelEntity;

    /**
     * nonofficialorganizationupdate Delightful modelstatus(writeo clockcopylogic).
     *
     * @param ProviderDataIsolation $dataIsolation dataisolationobject
     * @param ProviderModelEntity $officialModel officialmodelactualbody
     * @return string organizationmodelID
     */
    public function updateDelightfulModelStatus(
        ProviderDataIsolation $dataIsolation,
        ProviderModelEntity $officialModel
    ): string;
}
