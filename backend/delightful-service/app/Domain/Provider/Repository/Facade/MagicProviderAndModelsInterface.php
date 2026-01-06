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
 * 组织下的 Delightful 服务商及模型的相关接口（非官方组织才有 Delightful 服务商）.
 */
interface DelightfulProviderAndModelsInterface
{
    /**
     * 获取组织下的 Delightful 服务商配置（不含模型详情）.
     */
    public function getDelightfulProvider(ProviderDataIsolation $dataIsolation, Category $category, ?Status $status = null): ?ProviderConfigDTO;

    /**
     * 根据组织编码和类别获取 Delightful 服务商模型列表.
     *
     * @param string $organizationCode 组织编码
     * @param null|Category $category 服务商类别，为空时返回所有分类模型
     * @return array<ProviderModelEntity> Delightful 服务商模型实体数组
     */
    public function getDelightfulEnableModels(string $organizationCode, ?Category $category = null): array;

    /**
     * 根据 modelParentId 获取组织 Delightful 模型.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离对象
     * @param string $modelParentId 模型父ID
     * @return null|ProviderModelEntity 找到的组织模型实体，不存在则返回null
     */
    public function getDelightfulModelByParentId(ProviderDataIsolation $dataIsolation, string $modelParentId): ?ProviderModelEntity;

    /**
     * 根据ID获取组织 Delightful 模型.
     *
     * @param int $id 模型ID`
     * @return null|ProviderModelEntity 找到的模型实体，不存在则返回null
     */
    public function getDelightfulModelById(int $id): ?ProviderModelEntity;

    /**
     * 非官方组织更新 Delightful 模型状态（写时复制逻辑）.
     *
     * @param ProviderDataIsolation $dataIsolation 数据隔离对象
     * @param ProviderModelEntity $officialModel 官方模型实体
     * @return string 组织模型ID
     */
    public function updateDelightfulModelStatus(
        ProviderDataIsolation $dataIsolation,
        ProviderModelEntity $officialModel
    ): string;
}
