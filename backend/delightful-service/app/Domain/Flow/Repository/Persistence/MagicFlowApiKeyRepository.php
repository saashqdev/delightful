<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowApiKeyEntity;
use App\Domain\Flow\Entity\ValueObject\ApiKeyType;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowApiKeyQuery;
use App\Domain\Flow\Factory\DelightfulFlowApiKeyFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowApiKeyRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowApiKeyModel;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowApiKeyRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowApiKeyRepositoryInterface
{
    protected bool $filterOrganizationCode = true;

    public function getBySecretKey(FlowDataIsolation $dataIsolation, string $secretKey): ?DelightfulFlowApiKeyEntity
    {
        if (empty($secretKey)) {
            return null;
        }
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowApiKeyModel::query());
        /** @var null|DelightfulFlowApiKeyModel $model */
        $model = $builder->where('secret_key', $secretKey)->first();
        return $model ? DelightfulFlowApiKeyFactory::modelToEntity($model) : null;
    }

    public function getByCode(FlowDataIsolation $dataIsolation, string $code, ?string $creator = null): ?DelightfulFlowApiKeyEntity
    {
        if (empty($code)) {
            return null;
        }
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowApiKeyModel::query());
        $builder->where('code', $code);
        if (! is_null($creator)) {
            $builder->where('created_uid', $creator);
        }
        /** @var null|DelightfulFlowApiKeyModel $model */
        $model = $builder->first();
        return $model ? DelightfulFlowApiKeyFactory::modelToEntity($model) : null;
    }

    public function exist(FlowDataIsolation $dataIsolation, DelightfulFlowApiKeyEntity $magicFlowApiKeyEntity): bool
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowApiKeyModel::query());
        $builder->where('flow_code', $magicFlowApiKeyEntity->getFlowCode())
            ->where('conversation_id', $magicFlowApiKeyEntity->getConversationId());
        /* @phpstan-ignore-next-line */
        if ($magicFlowApiKeyEntity->getType() === ApiKeyType::Personal) {
            $builder->where('type', $magicFlowApiKeyEntity->getType()->value)
                ->where('created_uid', $magicFlowApiKeyEntity->getCreator());
        }

        return $builder->exists();
    }

    /**
     * @return array{total: int, list: array<DelightfulFlowApiKeyEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowApiKeyQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowApiKeyModel::query());
        if ($query->getFlowCode()) {
            $builder->where('flow_code', $query->getFlowCode());
        }
        if ($query->getType()) {
            $builder->where('type', $query->getType());
        }
        if ($query->getCreator()) {
            $builder->where('created_uid', $query->getCreator());
        }

        $data = $this->getByPage($builder, $page, $query);
        if (! empty($data['list'])) {
            $list = [];
            foreach ($data['list'] as $model) {
                $list[] = DelightfulFlowApiKeyFactory::modelToEntity($model);
            }
            $data['list'] = $list;
        }
        return $data;
    }

    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowApiKeyEntity $magicFlowApiKeyEntity): DelightfulFlowApiKeyEntity
    {
        $model = $this->createBuilder($dataIsolation, DelightfulFlowApiKeyModel::query())
            ->where('code', $magicFlowApiKeyEntity->getCode())
            ->first();
        if (! $model) {
            $model = new DelightfulFlowApiKeyModel();
        }

        $model->fill($this->getAttributes($magicFlowApiKeyEntity));
        $model->save();
        $magicFlowApiKeyEntity->setId($model->id);
        return $magicFlowApiKeyEntity;
    }

    public function destroy(FlowDataIsolation $dataIsolation, string $code): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowApiKeyModel::query());
        $builder->where('code', $code)->delete();
    }
}
