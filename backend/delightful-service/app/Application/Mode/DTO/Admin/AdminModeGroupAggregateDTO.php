<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Mode\DTO\Admin;

use App\Application\Mode\DTO\ModeGroupModelDTO;
use App\Infrastructure\Core\AbstractDTO;

class AdminModeGroupAggregateDTO extends AbstractDTO
{
    protected ?AdminModeGroupDTO $group = null;

    /**
     * @var ModeGroupModelDTO[] theminutegrouptoshouldmodeldetailedinfoarray
     */
    protected array $models = [];

    /**
     * @var ModeGroupModelDTO[] theminutegrouptoshouldgraphlikemodeldetailedinfoarray(VLM)
     */
    protected array $imageModels = [];

    public function __construct(null|AdminModeGroupDTO|array $group = null, array $models = [], array $imageModels = [])
    {
        if (! is_null($group)) {
            $this->group = $group instanceof AdminModeGroupDTO ? $group : new AdminModeGroupDTO($group);
        }
        $this->models = $models;
        $this->imageModels = $imageModels;
    }

    public function getGroup(): ?AdminModeGroupDTO
    {
        return $this->group;
    }

    public function setGroup(AdminModeGroupDTO|array $group): void
    {
        $this->group = $group instanceof AdminModeGroupDTO ? $group : new AdminModeGroupDTO($group);
    }

    /**
     * @return array[]|ModeGroupModelDTO[]
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
     * @return array[]|ModeGroupModelDTO[]
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
