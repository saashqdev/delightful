<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\DTO;

use App\Domain\Provider\Entity\ProviderModelEntity;
use Hyperf\Codec\Json;

/**
 * service_provider_config_id 对应的service商+model列表。
 *
 * 同oneservice商在different的organization下有different的 service_provider_config_id。
 * oneservice_provider_config_id对应多个具体的model。
 */
class ProviderConfigModelsDTO extends ProviderConfigDTO
{
    /**
     * ProvidermodelDTOarray.
     * @var ProviderModelDetailDTO[]
     */
    protected array $models = [];

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    // ===== model相关field的Getter/Setter =====

    /**
     * @return ProviderModelDetailDTO[]
     */
    public function getModels(): array
    {
        return $this->models;
    }

    public function setModels(null|array|string $models): void
    {
        if ($models === null) {
            $this->models = [];
        } elseif (is_string($models)) {
            $decoded = Json::decode($models);
            $this->models = is_array($decoded) ? $decoded : [];
        } else {
            $this->models = $models;
        }
    }

    public function hasModels(): bool
    {
        return ! empty($this->models);
    }

    public function addModel(ProviderModelEntity $model): void
    {
        // 把modelconvert为ProviderModelDetailDTO
        $modelDTO = new ProviderModelDetailDTO($model->toArray());
        $this->models[] = $modelDTO;
    }
}
