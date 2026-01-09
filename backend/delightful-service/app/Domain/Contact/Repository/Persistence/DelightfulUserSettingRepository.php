<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Persistence;

use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\Query\DelightfulUserSettingQuery;
use App\Domain\Contact\Factory\DelightfulUserSettingFactory;
use App\Domain\Contact\Repository\Facade\DelightfulUserSettingRepositoryInterface;
use App\Domain\Contact\Repository\Persistence\Model\UserSettingModel;
use App\Infrastructure\Core\ValueObject\Page;
use DateTime;

class DelightfulUserSettingRepository extends AbstractDelightfulContactRepository implements DelightfulUserSettingRepositoryInterface
{
    protected bool $filterOrganizationCode = true;

    public function save(DataIsolation $dataIsolation, DelightfulUserSettingEntity $delightfulUserSettingEntity): DelightfulUserSettingEntity
    {
        if (! $delightfulUserSettingEntity->getId()) {
            $model = new UserSettingModel();
        } else {
            $builder = $this->createContactBuilder($dataIsolation, UserSettingModel::query());
            $model = $builder->where('id', $delightfulUserSettingEntity->getId())->first();
        }

        $model->fill(DelightfulUserSettingFactory::createModel($delightfulUserSettingEntity));
        $model->save();

        $delightfulUserSettingEntity->setId($model->id);
        return $delightfulUserSettingEntity;
    }

    public function get(DataIsolation $dataIsolation, string $key): ?DelightfulUserSettingEntity
    {
        $builder = $this->createContactBuilder($dataIsolation, UserSettingModel::query());

        /** @var null|UserSettingModel $model */
        $model = $builder->where('user_id', $dataIsolation->getCurrentUserId())
            ->where('key', $key)
            ->first();

        if (! $model) {
            return null;
        }

        return DelightfulUserSettingFactory::createEntity($model);
    }

    /**
     * @return array{total: int, list: array<DelightfulUserSettingEntity>}
     */
    public function queries(DataIsolation $dataIsolation, DelightfulUserSettingQuery $query, Page $page): array
    {
        $builder = $this->createContactBuilder($dataIsolation, UserSettingModel::query());

        if ($query->getUserId()) {
            $builder->where('user_id', $query->getUserId());
        }

        if ($query->getKey()) {
            $builder->where('key', 'like', '%' . $query->getKey() . '%');
        }

        if (! empty($query->getKeys())) {
            $builder->whereIn('key', $query->getKeys());
        }

        $result = $this->getByPage($builder, $page, $query);

        $list = [];
        /** @var UserSettingModel $model */
        foreach ($result['list'] as $model) {
            $list[] = DelightfulUserSettingFactory::createEntity($model);
        }

        return [
            'total' => $result['total'],
            'list' => $list,
        ];
    }

    /**
     * pass delightfulId + key getusersetting（跨organization）.
     */
    public function getByDelightfulId(string $delightfulId, string $key): ?DelightfulUserSettingEntity
    {
        /** @var null|UserSettingModel $model */
        $model = UserSettingModel::query()
            ->where('delightful_id', $delightfulId)
            ->where('key', $key)
            ->first();

        return $model ? DelightfulUserSettingFactory::createEntity($model) : null;
    }

    /**
     * pass delightfulId saveusersetting（跨organization），若已存在same key 则update。
     */
    public function saveByDelightfulId(string $delightfulId, DelightfulUserSettingEntity $delightfulUserSettingEntity): DelightfulUserSettingEntity
    {
        // write delightfulId
        $delightfulUserSettingEntity->setDelightfulId($delightfulId);

        // 查找现有record
        $model = UserSettingModel::query()
            ->where('delightful_id', $delightfulId)
            ->where('key', $delightfulUserSettingEntity->getKey())
            ->first();

        if (! $model) {
            $model = new UserSettingModel();
        } else {
            $delightfulUserSettingEntity->setId($model->id);
        }

        $model->fill(DelightfulUserSettingFactory::createModel($delightfulUserSettingEntity));
        $model->save();

        $delightfulUserSettingEntity->setId($model->id);
        return $delightfulUserSettingEntity;
    }

    /**
     * get全局configuration（organization_code/user_id/delightful_id 均为 NULL）。
     */
    public function getGlobal(string $key): ?DelightfulUserSettingEntity
    {
        /** @var null|UserSettingModel $model */
        $model = UserSettingModel::query()
            ->whereNull('organization_code')
            ->whereNull('user_id')
            ->whereNull('delightful_id')
            ->where('key', $key)
            ->first();

        return $model ? DelightfulUserSettingFactory::createEntity($model) : null;
    }

    /**
     * save全局configuration.
     */
    public function saveGlobal(DelightfulUserSettingEntity $delightfulUserSettingEntity): DelightfulUserSettingEntity
    {
        // 查找现有record
        /** @var null|UserSettingModel $model */
        $model = UserSettingModel::query()
            ->whereNull('organization_code')
            ->whereNull('user_id')
            ->whereNull('delightful_id')
            ->where('key', $delightfulUserSettingEntity->getKey())
            ->first();

        if (! $model) {
            $model = new UserSettingModel();
        } else {
            $delightfulUserSettingEntity->setId($model->id);
        }

        // use工厂generatedata后手动覆盖 NULL field
        $delightfulUserSettingEntity->setOrganizationCode(null);
        $delightfulUserSettingEntity->setUserId(null);
        $delightfulUserSettingEntity->setDelightfulId(null);
        $delightfulUserSettingEntity->setCreatedAt(new DateTime());
        $delightfulUserSettingEntity->setUpdatedAt(new DateTime());
        $model->fill(DelightfulUserSettingFactory::createModel($delightfulUserSettingEntity));

        $model->save();

        $delightfulUserSettingEntity->setId($model->id);
        return $delightfulUserSettingEntity;
    }
}
