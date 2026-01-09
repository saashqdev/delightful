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
     * @var ModeGroupModelDTO[] theminutegrouptoshouldmodeldetailedinfoarray
     */
    protected array $models = [];

    /**
     * @var ModeGroupModelDTO[] theminutegrouptoshouldgraphlikemodeldetailedinfoarray(VLM)
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
     * addmodel.
     */
    public function addModel(ModeGroupModelDTO $model): void
    {
        if (! $this->hasModelId($model->getModelId())) {
            $this->models[] = $model;
        }
    }

    /**
     * addmodelID(tobackcompatible,butnotrecommendeduse).
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
     * moveexceptmodel.
     */
    public function removeModelId(string $modelId): void
    {
        foreach ($this->models as $key => $model) {
            if ($model->getModelId() === $modelId) {
                unset($this->models[$key]);
                $this->models = array_values($this->models); // 重newindex
                break;
            }
        }
    }

    /**
     * checkwhethercontainfingersetmodelID.
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
     * getmodelIDarray(tobackcompatible).
     * @return string[]
     */
    public function getModelIds(): array
    {
        return array_map(fn ($model) => $model->getModelId(), $this->models);
    }

    /**
     * setmodelIDarray(tobackcompatible,butnotrecommendeduse).
     * @param string[] $modelIds
     */
    public function setModelIds(array $modelIds): void
    {
        // thismethodretainuseattobackcompatible,butactualupneedcompletemodelinfo
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
