<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Persistence;

use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Repository\Persistence\Model\ProviderModelModel;
use App\Infrastructure\Core\AbstractQuery;
use App\Infrastructure\Core\AbstractRepository;
use App\Infrastructure\Core\DataIsolation\DataIsolationFilter;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use DateTime;
use Hyperf\Codec\Json;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Query\Builder;

abstract class AbstractProviderModelRepository extends AbstractRepository
{
    use DataIsolationFilter;

    protected bool $filterOrganizationCode = false;

    protected array $attributeMaps = [];

    /**
     * createnewmodel实body.
     */
    public function create(ProviderDataIsolation $dataIsolation, ProviderModelEntity $modelEntity): ProviderModelEntity
    {
        $modelEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        // createnewrecord
        if ($modelEntity->getId() === null) {
            $modelEntity->setId(IdGenerator::getSnowId());
        }
        // checktimefieldwhetherfornull
        if ($modelEntity->getCreatedAt() === null || $modelEntity->getUpdatedAt() === null) {
            $modelEntity->setCreatedAt(new DateTime());
            $modelEntity->setUpdatedAt(new DateTime());
            $modelEntity->setDeletedAt(null);
        }

        $data = $modelEntity->toArray();
        $data['disabled_by'] = $data['disabled_by'] ?? '';
        // createnewrecord
        ProviderModelModel::query()->create($data);
        return $modelEntity;
    }

    /**
     * will实bodyserializeforarray，containJSONserializecomplexfield.
     */
    protected function serializeEntityToArray(ProviderModelEntity $entity): array
    {
        $entityArray = $entity->toArray();
        $entityArray['config'] = Json::encode($entity->getConfig() ? $entity->getConfig()->toArray() : []);
        $entityArray['translate'] = Json::encode($entity->getTranslate() ?: []);
        $entityArray['visible_organizations'] = Json::encode($entity->getVisibleOrganizations());
        $entityArray['visible_applications'] = Json::encode($entity->getVisibleApplications());
        $entityArray['visible_packages'] = Json::encode($entity->getVisiblePackages());
        $entityArray['disabled_by'] = $entityArray['disabled_by'] ?? '';

        return $entityArray;
    }

    /**
     * @return array{total: int, list: array|Collection}
     */
    protected function getByPage(Builder|\Hyperf\Database\Model\Builder $builder, Page $page, ?AbstractQuery $query = null): array
    {
        if ($query) {
            foreach ($query->getOrder() as $column => $order) {
                $builder->orderBy($column, $order);
            }
            if (! empty($query->getSelect())) {
                $builder->select($query->getSelect());
            }
        }
        if (! $page->isEnabled()) {
            $list = $builder->get();
            /* @phpstan-ignore-next-line */
            return [
                'total' => count($list),
                'list' => $list,
            ];
        }
        $total = -1;
        if ($page->isTotal()) {
            $total = $builder->count();
        }
        $list = [];
        if ($total > 0 || ! $page->isTotal()) {
            $list = $builder->forPage($page->getPage(), $page->getPageNum())->get();
        }
        return [
            'total' => $total,
            'list' => $list,
        ];
    }
}
