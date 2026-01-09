<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Persistence;

use App\Domain\Provider\Entity\AiAbilityEntity;
use App\Domain\Provider\Entity\ValueObject\AiAbilityCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\Query\AiAbilityQuery;
use App\Domain\Provider\Repository\Facade\AiAbilityRepositoryInterface;
use App\Domain\Provider\Repository\Persistence\Model\AiAbilityModel;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Provider\Assembler\AiAbilityAssembler;

/**
 * AI 能力仓储implement.
 */
class AiAbilityRepository extends AbstractModelRepository implements AiAbilityRepositoryInterface
{
    protected bool $filterOrganizationCode = true;

    /**
     * according to能力代码getAI能力实体.
     */
    public function getByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code): ?AiAbilityEntity
    {
        $builder = $this->createBuilder($dataIsolation, AiAbilityModel::query());
        $model = $builder->where('code', $code->value)->first();

        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    /**
     * get所有AI能力list.
     */
    public function getAll(ProviderDataIsolation $dataIsolation): array
    {
        $builder = $this->createBuilder($dataIsolation, AiAbilityModel::query());
        $models = $builder->orderBy('sort_order')->get();

        $entities = [];
        foreach ($models as $model) {
            $entities[] = $this->modelToEntity($model);
        }

        return $entities;
    }

    /**
     * according toIDgetAI能力实体.
     */
    public function getById(ProviderDataIsolation $dataIsolation, int $id): ?AiAbilityEntity
    {
        $builder = $this->createBuilder($dataIsolation, AiAbilityModel::query());
        $model = $builder->where('id', $id)->first();

        if ($model === null) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    /**
     * saveAI能力实体.
     */
    public function save(AiAbilityEntity $entity): bool
    {
        $model = new AiAbilityModel();
        $model->code = $entity->getCode()->value;
        $model->organization_code = $entity->getOrganizationCode();
        $model->name_i18n = $entity->getName();
        $model->description_i18n = $entity->getDescription();
        $model->icon = $entity->getIcon();
        $model->sort_order = $entity->getSortOrder();
        $model->status = $entity->getStatus()->value;
        $model->config = '';

        $result = $model->save();

        if ($result) {
            $entity->setId($model->id);
            // useIDencryptconfig并update
            $encryptedConfig = AiAbilityAssembler::encodeConfig($entity->getConfig(), (string) $model->id);
            $model->config = $encryptedConfig;
            $model->save();
        }

        return $result;
    }

    /**
     * updateAI能力实体.
     */
    public function update(AiAbilityEntity $entity): bool
    {
        $model = AiAbilityModel::query()
            ->where('organization_code', $entity->getOrganizationCode())
            ->where('code', $entity->getCode()->value)
            ->first();
        if ($model === null) {
            return false;
        }

        $model->name_i18n = $entity->getName();
        $model->description_i18n = $entity->getDescription();
        $model->icon = $entity->getIcon();
        $model->sort_order = $entity->getSortOrder();
        $model->status = $entity->getStatus()->value;

        // encryptconfig后再save
        $model->config = AiAbilityAssembler::encodeConfig($entity->getConfig(), (string) $model->id);

        return $model->save();
    }

    /**
     * according tocodeupdate（支持选择性update）.
     */
    public function updateByCode(ProviderDataIsolation $dataIsolation, AiAbilityCode $code, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        // 如果needupdateconfig，先getrecordID进行encrypt
        if (! empty($data['config'])) {
            $builder = $this->createBuilder($dataIsolation, AiAbilityModel::query());
            $model = $builder->where('code', $code->value)->first();

            if ($model === null) {
                return false;
            }

            // encryptconfig
            $data['config'] = AiAbilityAssembler::encodeConfig($data['config'], (string) $model->id);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        $builder = $this->createBuilder($dataIsolation, AiAbilityModel::query());
        return $builder->where('code', $code->value)->update($data) > 0;
    }

    /**
     * paginationqueryAI能力list.
     *
     * @return array{total: int, list: array<AiAbilityEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, AiAbilityQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, AiAbilityModel::query());

        $result = $this->getByPage($builder, $page, $query);

        $list = [];
        foreach ($result['list'] as $model) {
            $list[] = $this->modelToEntity($model);
        }

        return [
            'total' => $result['total'],
            'list' => $list,
        ];
    }

    /**
     * 将Model转换为Entity.
     */
    private function modelToEntity(AiAbilityModel $model): AiAbilityEntity
    {
        $entity = new AiAbilityEntity();
        $entity->setId($model->id);
        $entity->setCode($model->code);
        $entity->setOrganizationCode($model->organization_code);
        $entity->setName($model->name_i18n);
        $entity->setDescription($model->description_i18n);
        $entity->setIcon($model->icon);
        $entity->setSortOrder($model->sort_order);
        $entity->setStatus($model->status);

        // 解析config（兼容oldJSONformat和newencryptformat）
        $config = $model->config ?? '';
        if (empty($config)) {
            $config = [];
        } elseif (is_string($config)) {
            // 尝试作为JSON解析
            $jsonDecoded = json_decode($config, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded)) {
                // JSON解析success，说明是旧数据（未encrypt）
                $config = $jsonDecoded;
            } else {
                // JSON解析fail，说明是encrypt数据，进行decrypt
                $config = AiAbilityAssembler::decodeConfig($config, (string) $model->id);
            }
        } else {
            $config = [];
        }
        $entity->setConfig($config);

        return $entity;
    }
}
