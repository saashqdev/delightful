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
     * @var ModeGroupModelDTO[] 该分group对应的model详细infoarray
     */
    protected array $models = [];

    /**
     * @var ModeGroupModelDTO[] 该分group对应的图像model详细infoarray（VLM）
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
     * 添加modelID（to后compatible，butnot推荐use）.
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
     * 移exceptmodel.
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
     * checkwhethercontain指定modelID.
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
     * getmodelquantity.
     */
    public function getModelCount(): int
    {
        return count($this->models);
    }

    /**
     * getmodelIDarray（to后compatible）.
     * @return string[]
     */
    public function getModelIds(): array
    {
        return array_map(fn ($model) => $model->getModelId(), $this->models);
    }

    /**
     * setmodelIDarray（to后compatible，butnot推荐use）.
     * @param string[] $modelIds
     */
    public function setModelIds(array $modelIds): void
    {
        // 这个method保留useatto后compatible，butactual上need完整的modelinfo
        // suggestionuse setModels() method
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
