<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\OrganizationEnvironment\Repository;

use App\Domain\OrganizationEnvironment\Entity\OrganizationEntity;
use App\Domain\OrganizationEnvironment\Entity\ValueObject\OrganizationSyncStatus;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Persistence\Model\OrganizationModel;
use App\Infrastructure\Core\ValueObject\Page;
use DateTime;
use Hyperf\Database\Model\Builder;

use function Hyperf\Support\now;

/**
 * organizationwarehouselibraryimplement.
 */
class OrganizationRepository implements OrganizationRepositoryInterface
{
    /**
     * saveorganization.
     */
    public function save(OrganizationEntity $organizationEntity): OrganizationEntity
    {
        $data = [
            'delightful_organization_code' => $organizationEntity->getDelightfulOrganizationCode(),
            'name' => $organizationEntity->getName(),
            'platform_type' => $organizationEntity->getPlatformType(),
            'logo' => $organizationEntity->getLogo(),
            'introduction' => $organizationEntity->getIntroduction(),
            'contact_user' => $organizationEntity->getContactUser(),
            'contact_mobile' => $organizationEntity->getContactMobile(),
            'industry_type' => $organizationEntity->getIndustryType(),
            'number' => $organizationEntity->getNumber(),
            'status' => $organizationEntity->getStatus(),
            'creator_id' => $organizationEntity->getCreatorId(),
            'type' => $organizationEntity->getType(),
            'seats' => $organizationEntity->getSeats(),
            'sync_type' => $organizationEntity->getSyncType(),
            'sync_status' => $organizationEntity->getSyncStatus()?->value,
            'sync_time' => $organizationEntity->getSyncTime(),
            'updated_at' => $organizationEntity->getUpdatedAt() ?? now(),
        ];

        if ($organizationEntity->shouldCreate()) {
            $data['created_at'] = $organizationEntity->getCreatedAt() ?? now();

            $model = OrganizationModel::create($data);
            $organizationEntity->setId($model->id);
        } else {
            // usemodelupdatebyconvenientuse casts process JSON anddatefield
            $model = OrganizationModel::query()
                ->where('id', $organizationEntity->getId())
                ->first();
            if ($model) {
                $model->fill($data);
                $model->save();
            }
        }

        return $organizationEntity;
    }

    /**
     * according toIDgetorganization.
     */
    public function getById(int $id): ?OrganizationEntity
    {
        $model = OrganizationModel::query()
            ->where('id', $id)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * according toencodinggetorganization.
     */
    public function getByCode(string $code): ?OrganizationEntity
    {
        $model = OrganizationModel::query()
            ->where('delightful_organization_code', $code)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * according toencodinglistbatchquantitygetorganization.
     */
    public function getByCodes(array $codes): array
    {
        if (empty($codes)) {
            return [];
        }

        $models = OrganizationModel::query()
            ->whereIn('delightful_organization_code', $codes)
            ->get();

        $organizations = [];
        foreach ($models as $model) {
            $organizations[] = $this->mapToEntity($model);
        }

        return $organizations;
    }

    /**
     * according tonamegetorganization.
     */
    public function getByName(string $name): ?OrganizationEntity
    {
        $model = OrganizationModel::query()
            ->where('name', $name)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * queryorganizationlist.
     */
    public function queries(Page $page, ?array $filters = null): array
    {
        $query = OrganizationModel::query();

        // applicationfilteritemitem
        $this->applyFilters($query, $filters);

        // gettotal
        $total = $query->count();

        // sort:priorityusefilterdevicemiddlesortfield,nothendefaultbycreatetimereverse order
        $orderBy = $filters['order_by'] ?? null;
        $orderDirection = strtolower((string) ($filters['order_direction'] ?? '')) === 'asc' ? 'asc' : 'desc';
        if (! empty($orderBy)) {
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('id', 'asc');
        }

        // paginationquery
        $models = $query
            ->forPage($page->getPage(), $page->getPageNum())
            ->get();

        $organizations = [];
        foreach ($models as $model) {
            $organizations[] = $this->mapToEntity($model);
        }

        return [
            'total' => $total,
            'list' => $organizations,
        ];
    }

    /**
     * deleteorganization.
     */
    public function delete(OrganizationEntity $organizationEntity): void
    {
        $model = OrganizationModel::query()
            ->where('id', $organizationEntity->getId())
            ->first();

        if ($model) {
            $model->delete();
        }
    }

    /**
     * checkencodingwhetheralreadyexistsin.
     */
    public function existsByCode(string $code, ?int $excludeId = null): bool
    {
        $query = OrganizationModel::query()->where('delightful_organization_code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * applicationfilteritemitem.
     */
    private function applyFilters(Builder $query, ?array $filters): void
    {
        if (! $filters) {
            return;
        }

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (! empty($filters['delightful_organization_code'])) {
            $query->where('delightful_organization_code', $filters['delightful_organization_code']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['creator_id'])) {
            $query->where('creator_id', $filters['creator_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', (int) $filters['type']);
        }

        // syncstatusfilter
        if (isset($filters['sync_status'])) {
            $query->where('sync_status', (int) $filters['sync_status']);
        }

        // createtimeregionbetweenfilter
        if (! empty($filters['created_at_start'])) {
            $query->where('created_at', '>=', $filters['created_at_start'] . ' 00:00:00');
        }
        if (! empty($filters['created_at_end'])) {
            $query->where('created_at', '<=', $filters['created_at_end'] . ' 23:59:59');
        }
    }

    /**
     * willmodelmappingforactualbody.
     */
    private function mapToEntity(OrganizationModel $model): OrganizationEntity
    {
        $entity = new OrganizationEntity();
        $entity->setId($model->id);
        $entity->setDelightfulOrganizationCode($model->delightful_organization_code);
        $entity->setName($model->name);
        $entity->setPlatformType($model->platform_type);
        $entity->setLogo($model->logo);
        $entity->setIntroduction($model->introduction);
        $entity->setContactUser($model->contact_user);
        $entity->setContactMobile($model->contact_mobile);
        $entity->setIndustryType($model->industry_type);
        $entity->setNumber($model->number);
        $entity->setStatus($model->status);
        $entity->setCreatorId($model->creator_id);
        $entity->setType($model->type);
        $entity->setSeats($model->seats);
        $entity->setSyncType($model->sync_type);
        if ($model->sync_status !== null) {
            $entity->setSyncStatus(OrganizationSyncStatus::from((int) $model->sync_status));
        }
        if ($model->sync_time) {
            $entity->setSyncTime(new DateTime($model->sync_time->toDateTimeString()));
        }

        if ($model->created_at) {
            $entity->setCreatedAt(new DateTime($model->created_at->toDateTimeString()));
        }

        if ($model->updated_at) {
            $entity->setUpdatedAt(new DateTime($model->updated_at->toDateTimeString()));
        }

        if ($model->deleted_at) {
            $entity->setDeletedAt(new DateTime($model->deleted_at->toDateTimeString()));
        }

        return $entity;
    }
}
