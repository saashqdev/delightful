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

    public function save(DataIsolation $dataIsolation, DelightfulUserSettingEntity $magicUserSettingEntity): DelightfulUserSettingEntity
    {
        if (! $magicUserSettingEntity->getId()) {
            $model = new UserSettingModel();
        } else {
            $builder = $this->createContactBuilder($dataIsolation, UserSettingModel::query());
            $model = $builder->where('id', $magicUserSettingEntity->getId())->first();
        }

        $model->fill(DelightfulUserSettingFactory::createModel($magicUserSettingEntity));
        $model->save();

        $magicUserSettingEntity->setId($model->id);
        return $magicUserSettingEntity;
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
     * 通过 magicId + key 获取用户设置（跨组织）.
     */
    public function getByDelightfulId(string $magicId, string $key): ?DelightfulUserSettingEntity
    {
        /** @var null|UserSettingModel $model */
        $model = UserSettingModel::query()
            ->where('magic_id', $magicId)
            ->where('key', $key)
            ->first();

        return $model ? DelightfulUserSettingFactory::createEntity($model) : null;
    }

    /**
     * 通过 magicId 保存用户设置（跨组织），若已存在相同 key 则更新。
     */
    public function saveByDelightfulId(string $magicId, DelightfulUserSettingEntity $magicUserSettingEntity): DelightfulUserSettingEntity
    {
        // 写入 magicId
        $magicUserSettingEntity->setDelightfulId($magicId);

        // 查找现有记录
        $model = UserSettingModel::query()
            ->where('magic_id', $magicId)
            ->where('key', $magicUserSettingEntity->getKey())
            ->first();

        if (! $model) {
            $model = new UserSettingModel();
        } else {
            $magicUserSettingEntity->setId($model->id);
        }

        $model->fill(DelightfulUserSettingFactory::createModel($magicUserSettingEntity));
        $model->save();

        $magicUserSettingEntity->setId($model->id);
        return $magicUserSettingEntity;
    }

    /**
     * 获取全局配置（organization_code/user_id/magic_id 均为 NULL）。
     */
    public function getGlobal(string $key): ?DelightfulUserSettingEntity
    {
        /** @var null|UserSettingModel $model */
        $model = UserSettingModel::query()
            ->whereNull('organization_code')
            ->whereNull('user_id')
            ->whereNull('magic_id')
            ->where('key', $key)
            ->first();

        return $model ? DelightfulUserSettingFactory::createEntity($model) : null;
    }

    /**
     * 保存全局配置.
     */
    public function saveGlobal(DelightfulUserSettingEntity $magicUserSettingEntity): DelightfulUserSettingEntity
    {
        // 查找现有记录
        /** @var null|UserSettingModel $model */
        $model = UserSettingModel::query()
            ->whereNull('organization_code')
            ->whereNull('user_id')
            ->whereNull('magic_id')
            ->where('key', $magicUserSettingEntity->getKey())
            ->first();

        if (! $model) {
            $model = new UserSettingModel();
        } else {
            $magicUserSettingEntity->setId($model->id);
        }

        // 使用工厂生成数据后手动覆盖 NULL 字段
        $magicUserSettingEntity->setOrganizationCode(null);
        $magicUserSettingEntity->setUserId(null);
        $magicUserSettingEntity->setDelightfulId(null);
        $magicUserSettingEntity->setCreatedAt(new DateTime());
        $magicUserSettingEntity->setUpdatedAt(new DateTime());
        $model->fill(DelightfulUserSettingFactory::createModel($magicUserSettingEntity));

        $model->save();

        $magicUserSettingEntity->setId($model->id);
        return $magicUserSettingEntity;
    }
}
