<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Persistence;

use App\Infrastructure\Core\AbstractRepository;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectOperationLogEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\ProjectOperationLogRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\ProjectOperationLogModel;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;

/**
 * Project operation log repository implementation.
 */
class ProjectOperationLogRepository extends AbstractRepository implements ProjectOperationLogRepositoryInterface
{
    public function __construct(
        protected ProjectOperationLogModel $operationLogModel
    ) {
    }

    /**
     * Save operation log.
     */
    public function save(ProjectOperationLogEntity $operationLog): ProjectOperationLogEntity
    {
        $attributes = $this->entityToModelAttributes($operationLog);

        if ($operationLog->getId() > 0) {
            // Update existing record
            $model = $this->operationLogModel::query()->find($operationLog->getId());
            if (! $model) {
                ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_NOT_FOUND, 'project.operation_log.not_found');
            }
            $model->update($attributes);
            return $this->modelToEntity($model->toArray());
        }

        // Create new record
        $attributes['id'] = IdGenerator::getSnowId();
        $model = $this->operationLogModel::query()->create($attributes);
        return $this->modelToEntity($model->toArray());
    }

    /**
     * Find operation log list by project ID.
     */
    public function findByProjectId(int $projectId, int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;

        $models = $this->operationLogModel::query()
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        return $models->map(function ($model) {
            return $this->modelToEntity($model->toArray());
        })->toArray();
    }

    /**
     * Find operation log by project and user.
     */
    public function findByProjectAndUser(int $projectId, string $userId, int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;

        $models = $this->operationLogModel::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        return $models->map(function ($model) {
            return $this->modelToEntity($model->toArray());
        })->toArray();
    }

    /**
     * Find log by project and operation type.
     */
    public function findByProjectAndAction(int $projectId, string $action, int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;

        $models = $this->operationLogModel::query()
            ->where('project_id', $projectId)
            ->where('operation_action', $action)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($pageSize)
            ->get()->toArray();

        return array_map(function ($model) {
            return $this->modelToEntity($model);
        }, $models);
    }

    /**
     * Count operation logs by project ID.
     */
    public function countByProjectId(int $projectId): int
    {
        return $this->operationLogModel::query()
            ->where('project_id', $projectId)
            ->count();
    }

    /**
     * Find operation log by organization code.
     */
    public function findByOrganization(string $organizationCode, int $page = 1, int $pageSize = 20): array
    {
        $offset = ($page - 1) * $pageSize;

        $models = $this->operationLogModel::query()
            ->where('organization_code', $organizationCode)
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        return $models->map(function ($model) {
            return $this->modelToEntity($model->toArray());
        })->toArray();
    }

    /**
     * Convert entity to model attributes.
     */
    protected function entityToModelAttributes(ProjectOperationLogEntity $entity): array
    {
        return [
            'project_id' => $entity->getProjectId(),
            'user_id' => $entity->getUserId(),
            'organization_code' => $entity->getOrganizationCode(),
            'operation_action' => $entity->getOperationAction(),
            'resource_type' => $entity->getResourceType(),
            'resource_id' => $entity->getResourceId(),
            'resource_name' => $entity->getResourceName(),
            'operation_details' => $entity->getOperationDetails(),
            'operation_status' => $entity->getOperationStatus(),
            'ip_address' => $entity->getIpAddress(),
        ];
    }

    /**
     * Convert model data to entity.
     */
    protected function modelToEntity(array $data): ProjectOperationLogEntity
    {
        $entity = new ProjectOperationLogEntity();
        $entity->setId($data['id'] ?? 0)
            ->setProjectId($data['project_id'] ?? 0)
            ->setUserId($data['user_id'] ?? '')
            ->setOrganizationCode($data['organization_code'] ?? '')
            ->setOperationAction($data['operation_action'] ?? '')
            ->setResourceType($data['resource_type'] ?? '')
            ->setResourceId($data['resource_id'] ?? '')
            ->setResourceName($data['resource_name'] ?? '')
            ->setOperationDetails($data['operation_details'] ?? [])
            ->setOperationStatus($data['operation_status'] ?? 'success')
            ->setIpAddress($data['ip_address'] ?? '')
            ->setCreatedAt($data['created_at'] ?? '')
            ->setUpdatedAt($data['updated_at'] ?? '');

        return $entity;
    }
}
