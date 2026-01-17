<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Share\Repository\Persistence;

use App\Infrastructure\Core\AbstractRepository;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
use Delightful\BeDelightful\Domain\Share\Repository\Facade\ResourceShareRepositoryInterface;
use Delightful\BeDelightful\Domain\Share\Repository\Model\ResourceShareModel;
use Exception;
use Hyperf\Codec\Json;

/**
 * Resource share repository implementation.
 */
class ResourceShareRepository extends AbstractRepository implements ResourceShareRepositoryInterface
{
    /**
     * Constructor.
     */
    public function __construct(protected ResourceShareModel $model)
    {
    }

    /**
     * Get share by ID.
     *
     * @param int $shareId Share ID
     * @return null|ResourceShareEntity Resource share entity
     */
    public function getShareById(int $shareId): ?ResourceShareEntity
    {
        $model = $this->model->newQuery()->where('id', $shareId)->whereNull('deleted_at')->first();
        return $model ? $this->modelToEntity($model) : null;
    }

    /**
     * Get share by share code.
     *
     * @param string $shareCode Share code
     * @return null|ResourceShareEntity Resource share entity
     */
    public function getShareByCode(string $shareCode): ?ResourceShareEntity
    {
        $model = $this->model->newQuery()->where('share_code', $shareCode)->orderBy('id', 'desc')->first();
        return $model ? $this->modelToEntity($model) : null;
    }

    public function getShareByResourceId(string $resourceId): ?ResourceShareEntity
    {
        $model = $this->model->newQuery()->where('resource_id', $resourceId)->first();
        return $model ? $this->modelToEntity($model) : null;
    }

    /**
     * Find share corresponding to resource.
     *
     * @param string $resourceId Resource ID
     * @param ResourceType $resourceType Resource type
     * @param string $userId User ID
     * @return null|ResourceShareEntity Resource share entity
     */
    public function findByResource(string $resourceId, ResourceType $resourceType, string $userId): ?ResourceShareEntity
    {
        $model = $this->model->newQuery()
            ->where('resource_id', $resourceId)
            ->where('resource_type', $resourceType->value)
            ->where('creator', $userId)
            ->first();

        return $model ? $this->modelToEntity($model) : null;
    }

    /**
     * Create share record.
     *
     * @param array $data Share data
     * @return string Share ID
     */
    public function create(array $data): string
    {
        $model = new $this->model();
        $model->fill($data);
        $model->save();

        return (string) $model->id;
    }

    /**
     * Save share entity.
     *
     * @param ResourceShareEntity $shareEntity Resource share entity
     * @return ResourceShareEntity Saved resource share entity
     * @throws Exception
     */
    public function save(ResourceShareEntity $shareEntity): ResourceShareEntity
    {
        $data = $this->entityToArray($shareEntity);

        try {
            if ($shareEntity->getId() === 0) {
                // Create new record
                $data['id'] = IdGenerator::getSnowId();
                $model = $this->model::query()->create($data);
                $shareEntity->setId((int) $model->id);
            } else {
                // Update existing record
                $this->model->query()->withTrashed()->where('id', $shareEntity->getId())->update($data);
            }
            return $shareEntity;
        } catch (Exception $e) {
            throw new Exception('Failed to save share: ' . $e->getMessage());
        }
    }

    /**
     * Delete share.
     *
     * @param int $shareId Share ID
     * @param bool $forceDelete Whether to force delete (physical delete), default false for soft delete
     * @return bool Whether successful
     */
    public function delete(int $shareId, bool $forceDelete = false): bool
    {
        if ($forceDelete) {
            // Physical delete: directly remove from database
            $model = $this->model->newQuery()->withTrashed()->find($shareId);
            if ($model) {
                return $model->forceDelete();
            }
        } else {
            // Soft delete: use SoftDeletes trait
            $model = $this->model->newQuery()->find($shareId);
            if ($model) {
                return $model->delete();
            }
        }
        return false;
    }

    /**
     * Increment share view count.
     *
     * @param string $shareCode Share code
     * @return bool Whether successful
     */
    public function incrementViewCount(string $shareCode): bool
    {
        $result = $this->model->newQuery()
            ->where('share_code', $shareCode)
            ->increment('view_count');

        return $result > 0;
    }

    /**
     * Check if share code already exists.
     *
     * @param string $shareCode Share code
     * @return bool Whether exists
     */
    public function isShareCodeExists(string $shareCode): bool
    {
        return $this->model->newQuery()->where('share_code', $shareCode)->exists();
    }

    /**
     * Get share record list.
     *
     * @param array $conditions Conditions
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @return array Pagination data [total, list]
     */
    public function paginate(array $conditions, int $page = 1, int $pageSize = 20): array
    {
        $query = $this->model->newQuery();

        // Add query conditions
        foreach ($conditions as $field => $value) {
            if ($field == 'keyword') {
                $query->where($field, 'LIKE', '%' . $value . '%');
            } else {
                if ($value !== null) {
                    $query->where($field, $value);
                }
            }
        }

        // Sort by creation time in descending order
        $query->orderBy('id', 'desc');

        $query->whereNull('deleted_at');

        // Calculate total count
        $total = $query->count();

        // Get pagination data
        $models = $query->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        // Convert models to entities
        $items = [];
        foreach ($models as $model) {
            $items[] = $this->modelToEntity($model);
        }

        return [
            'total' => $total,
            'list' => $items,
        ];
    }

    public function getShareByResource(string $userId, string $resourceId, int $resourceType, bool $withTrashed = true): ?ResourceShareEntity
    {
        if ($withTrashed) {
            $query = ResourceShareModel::query()->withTrashed();
        } else {
            $query = ResourceShareModel::query();
        }
        if (! empty($userId)) {
            $query = $query->where('created_uid', $userId);
        }
        $model = $query->where('resource_id', $resourceId)->where('resource_type', $resourceType)->first();

        if (! $model) {
            return null;
        }

        return $this->modelToEntity($model);
    }

    /**
     * Convert PO model to entity.
     *
     * @param ResourceShareModel $model Model
     * @return ResourceShareEntity Entity
     */
    protected function modelToEntity(ResourceShareModel $model): ?ResourceShareEntity
    {
        $entity = new ResourceShareEntity();
        $entity->setId((int) $model->id);
        $entity->setResourceId((string) $model->resource_id);
        $entity->setResourceType((int) $model->resource_type);
        $entity->setResourceName((string) $model->resource_name);
        $entity->setShareCode(htmlspecialchars($model->share_code, ENT_QUOTES, 'UTF-8'));
        $entity->setShareType((int) $model->share_type);
        $entity->setPassword($model->password);

        // Safely handle dates - ensure correct format
        if ($model->expire_at) {
            $entity->setExpireAt($model->expire_at);
        }

        $entity->setViewCount($model->view_count);
        $entity->setCreatedUid($model->created_uid);
        $entity->setOrganizationCode(htmlspecialchars($model->organization_code, ENT_QUOTES, 'UTF-8'));

        // Handle target IDs if entity has this field
        /* @phpstan-ignore-next-line */
        if (property_exists($model, 'target_ids') && method_exists($entity, 'setTargetIds')) {
            $entity->setTargetIds($model->target_ids ?? '[]');
        }

        $extra = $model->extra ?? [];
        $extra = is_array($extra) ? $extra : Json::decode($extra);
        $entity->setExtra($extra);

        // Handle is_enabled field (dedicated for invitation links)
        $entity->setIsEnabled($model->is_enabled ?? true);

        // Handle password protection enabled field
        $entity->setIsPasswordEnabled($model->is_password_enabled ?? false);

        if ($model->created_at) {
            $entity->setCreatedAt($model->created_at);
        }

        if ($model->updated_at) {
            $entity->setUpdatedAt($model->updated_at);
        }

        if ($model->deleted_at) {
            $entity->setDeletedAt($model->deleted_at);
        }

        return $entity;
    }

    /**
     * Convert entity to array.
     *
     * @param ResourceShareEntity $entity Entity
     * @return array Array
     */
    protected function entityToArray(ResourceShareEntity $entity): array
    {
        return [
            'id' => $entity->getId(),
            'resource_id' => $entity->getResourceId(),
            'resource_type' => $entity->getResourceType(),
            'resource_name' => $entity->getResourceName(),
            'share_code' => $entity->getShareCode(),
            'share_type' => $entity->getShareType(),
            'password' => $entity->getPassword(),
            'is_password_enabled' => $entity->getIsPasswordEnabled() ? 1 : 0,
            'expire_at' => $entity->getExpireAt(),
            'view_count' => $entity->getViewCount(),
            'created_uid' => $entity->getCreatedUid(),
            'organization_code' => $entity->getOrganizationCode(),
            'extra' => Json::encode($entity->getExtra() ?? []),
            'is_enabled' => $entity->getIsEnabled() ? 1 : 0,
            'updated_at' => $entity->getUpdatedAt(),
            'deleted_at' => $entity->getDeletedAt(),
            'updated_uid' => $entity->getUpdatedUid(),
        ];
    }
}
