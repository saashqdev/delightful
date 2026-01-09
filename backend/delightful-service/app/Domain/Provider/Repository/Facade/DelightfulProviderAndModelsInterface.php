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
 * organization下的 Delightful service商及model的相关interface（非官方organization才有 Delightful service商）.
 */
interface DelightfulProviderAndModelsInterface
{
    /**
     * getorganization下的 Delightful service商configuration（not containmodeldetail）.
     */
    public function getDelightfulProvider(ProviderDataIsolation $dataIsolation, Category $category, ?Status $status = null): ?ProviderConfigDTO;

    /**
     * according toorganizationencoding和类别get Delightful service商modellist.
     *
     * @param string $organizationCode organizationencoding
     * @param null|Category $category service商类别，为空时return所有categorymodel
     * @return array<ProviderModelEntity> Delightful service商model实体array
     */
    public function getDelightfulEnableModels(string $organizationCode, ?Category $category = null): array;

    /**
     * according to modelParentId getorganization Delightful model.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param string $modelParentId model父ID
     * @return null|ProviderModelEntity 找到的organizationmodel实体，不存在则returnnull
     */
    public function getDelightfulModelByParentId(ProviderDataIsolation $dataIsolation, string $modelParentId): ?ProviderModelEntity;

    /**
     * according toIDgetorganization Delightful model.
     *
     * @param int $id modelID`
     * @return null|ProviderModelEntity 找到的model实体，不存在则returnnull
     */
    public function getDelightfulModelById(int $id): ?ProviderModelEntity;

    /**
     * 非官方organizationupdate Delightful modelstatus（写时复制逻辑）.
     *
     * @param ProviderDataIsolation $dataIsolation data隔离object
     * @param ProviderModelEntity $officialModel 官方model实体
     * @return string organizationmodelID
     */
    public function updateDelightfulModelStatus(
        ProviderDataIsolation $dataIsolation,
        ProviderModelEntity $officialModel
    ): string;
}
