<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Mode\DTO;

use App\Infrastructure\Core\AbstractDTO;

class ModeGroupAggregateDTO extends AbstractDTO
{
    protected ModeGroupDTO $group;

    /**
     * @var ModeGroupModelDTO[] 该分组对应的model详细infoarray
     */
    protected array $models = [];

    /**
     * @var ModeGroupModelDTO[] 该分组对应的图像model详细infoarray（VLM）
     */
    protected array $imageModels = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function getGroup(): ModeGroupDTO
    {
        return $this->group;
    }

    public function setGroup(array|ModeGroupDTO $group): void
    {
        $this->group = $group instanceof ModeGroupDTO ? $group : new ModeGroupDTO($group);
    }

    /**
     * @return ModeGroupModelDTO[]
     */
    public function getModels(): array
    {
        return $this->models;
    }

    public function setModels(array $models): void
    {
        $modelData = [];
        foreach ($models as $model) {
            $modelData[] = $model instanceof ModeGroupModelDTO ? $model : new ModeGroupModelDTO($model);
        }

        $this->models = $modelData;
    }

    /**
     * 添加model.
     */
    public function addModel(ModeGroupModelDTO $model): void
    {
        if (! $this->hasModelId($model->getModelId())) {
            $this->models[] = $model;
        }
    }

    /**
     * 添加modelID（向后兼容，但不推荐使用）.
     */
    public function addModelId(string $modelId): void
    {
        if (! $this->hasModelId($modelId)) {
            $model = new ModeGroupModelDTO();
            $model->setModelId($modelId);
            $this->models[] = $model;
        }
    }

    /**
     * 移除model.
     */
    public function removeModelId(string $modelId): void
    {
        foreach ($this->models as $key => $model) {
            if ($model->getModelId() === $modelId) {
                unset($this->models[$key]);
                $this->models = array_values($this->models); // 重新索引
                break;
            }
        }
    }

    /**
     * check是否包含指定modelID.
     */
    public function hasModelId(string $modelId): bool
    {
        foreach ($this->models as $model) {
            if ($model->getModelId() === $modelId) {
                return true;
            }
        }
        return false;
    }

    /**
     * getmodel数量.
     */
    public function getModelCount(): int
    {
        return count($this->models);
    }

    /**
     * getmodelIDarray（向后兼容）.
     * @return string[]
     */
    public function getModelIds(): array
    {
        return array_map(fn ($model) => $model->getModelId(), $this->models);
    }

    /**
     * setmodelIDarray（向后兼容，但不推荐使用）.
     * @param string[] $modelIds
     */
    public function setModelIds(array $modelIds): void
    {
        // 这个method保留用于向后兼容，但实际上需要完整的modelinfo
        // 建议使用 setModels() method
        $this->models = [];
        foreach ($modelIds as $modelId) {
            $model = new ModeGroupModelDTO();
            $model->setModelId($modelId);
            $this->models[] = $model;
        }
    }

    /**
     * @return ModeGroupModelDTO[]
     */
    public function getImageModels(): array
    {
        return $this->imageModels;
    }

    public function setImageModels(array $imageModels): void
    {
        $modelData = [];
        foreach ($imageModels as $model) {
            $modelData[] = $model instanceof ModeGroupModelDTO ? $model : new ModeGroupModelDTO($model);
        }

        $this->imageModels = $modelData;
    }
}
