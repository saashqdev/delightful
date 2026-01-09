<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Persistence;

use App\Domain\Chat\DTO\PageResponseDTO\DepartmentUsersPageResponseDTO;
use App\Domain\Contact\Entity\DelightfulDepartmentUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Repository\Facade\DelightfulDepartmentUserRepositoryInterface;
use App\Domain\Contact\Repository\Persistence\Model\DepartmentModel;
use App\Domain\Contact\Repository\Persistence\Model\DepartmentUserModel;
use Hyperf\DbConnection\Db;
use Psr\SimpleCache\CacheInterface;

class DelightfulDepartmentUserRepository implements DelightfulDepartmentUserRepositoryInterface
{
    public function __construct(
        protected DepartmentUserModel $departmentUserModel,
    ) {
    }

    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    public function getDepartmentUsersByUserIds(array $userIds, string $organizationCode): array
    {
        $query = $this->departmentUserModel->newQuery()
            ->whereIn('user_id', $userIds)
            ->where('organization_code', $organizationCode);
        $departmentUsers = Db::select($query->toSql(), $query->getBindings());
        return $this->getDepartmentUserEntities($departmentUsers);
    }

    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    public function getDepartmentUsersByUserIdsInDelightful(array $userIds): array
    {
        $query = $this->departmentUserModel->newQuery()->whereIn('user_id', $userIds);
        $departmentUsers = Db::select($query->toSql(), $query->getBindings());
        return $this->getDepartmentUserEntities($departmentUsers);
    }

    public function getDepartmentUsersByDepartmentId(string $departmentId, string $organizationCode, int $limit, int $offset): DepartmentUsersPageResponseDTO
    {
        $query = $this->departmentUserModel->newQuery()
            ->where('department_id', $departmentId)
            ->where('organization_code', $organizationCode)
            ->limit($limit)
            ->offset($offset);
        $departmentUsers = Db::select($query->toSql(), $query->getBindings());
        $items = $this->getDepartmentUserEntities($departmentUsers);
        $hasMore = count($items) === $limit;
        $pageToken = $hasMore ? (string) ($limit + $offset) : '';
        return new DepartmentUsersPageResponseDTO([
            'items' => $items,
            'page_token' => $pageToken,
            'has_more' => $hasMore,
        ]);
    }

    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    public function getDepartmentUsersByDepartmentIds(array $departmentIds, string $organizationCode, int $limit, array $fields = ['*']): array
    {
        $query = $this->departmentUserModel->newQuery()
            ->select($fields)
            ->whereIn('department_id', $departmentIds)
            ->where('organization_code', $organizationCode)
            ->limit($limit);
        $departmentUsers = Db::select($query->toSql(), $query->getBindings());
        return $this->getDepartmentUserEntities($departmentUsers);
    }

    public function getDepartmentIdsByUserIds(DataIsolation $dataIsolation, array $userIds, bool $withAllParentIds = false): array
    {
        $cache = di(CacheInterface::class);
        $key = 'DelightfulDepartmentUser:' . md5('department_ids_by_user_ids_' . implode('_', $userIds) . '_' . $dataIsolation->getCurrentOrganizationCode() . '_' . ($withAllParentIds ? 'all' : 'direct'));
        if ($cache->has($key)) {
            return (array) $cache->get($key);
        }
        $builder = DepartmentUserModel::query();
        $builder->whereIn('user_id', $userIds);
        $builder->where('organization_code', $dataIsolation->getCurrentOrganizationCode());
        $departmentUsers = Db::select($builder->toSql(), $builder->getBindings());
        $list = [];
        $departmentIds = [];
        foreach ($departmentUsers as $departmentUser) {
            $list[$departmentUser['user_id']][] = $departmentUser['department_id'];
            $departmentIds[] = $departmentUser['department_id'];
        }
        if ($withAllParentIds) {
            // get所havedepartmentinformation
            $departmentIds = array_values(array_unique($departmentIds));
            $departments = DepartmentModel::query()
                ->where('organization_code', $dataIsolation->getCurrentOrganizationCode())
                ->whereIn('department_id', $departmentIds)->pluck('path', 'department_id')->toArray();
            foreach ($list as $userId => $userDepartmentIds) {
                foreach ($userDepartmentIds as $departmentId) {
                    if (isset($departments[$departmentId])) {
                        $path = explode('/', $departments[$departmentId]);
                        $list[$userId] = array_merge($list[$userId], $path);
                    }
                }
                $list[$userId] = array_values(array_unique($list[$userId]));
            }
        }

        $cache->set($key, $list, 60);

        return $list;
    }

    public function createDepartmentUsers(array $createDepartmentUserDTOs): bool
    {
        return $this->departmentUserModel->newQuery()->insert($createDepartmentUserDTOs);
    }

    public function updateDepartmentUser(string $delightfulDepartmentUserPrimaryId, array $updateData): int
    {
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        return $this->departmentUserModel->newQuery()
            ->where('id', $delightfulDepartmentUserPrimaryId)
            ->update($updateData);
    }

    public function deleteDepartmentUsersByDelightfulIds(array $delightfulIds, string $departmentId, string $delightfulOrganizationCode): int
    {
        return (int) $this->departmentUserModel->newQuery()
            ->where('organization_code', $delightfulOrganizationCode)
            ->whereIn('delightful_id', $delightfulIds)
            ->where('department_id', $departmentId)
            ->delete();
    }

    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    public function searchDepartmentUsersByJobTitle(string $keyword, string $delightfulOrganizationCode): array
    {
        $res = $this->departmentUserModel::query()
            ->where('job_title', 'like', "%{$keyword}%")
            ->where('organization_code', $delightfulOrganizationCode)
            ->get()
            ->toArray();
        return array_map(fn ($item) => new DelightfulDepartmentUserEntity($item), $res);
    }

    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    private function getDepartmentUserEntities(array $departmentUsers): array
    {
        $departmentUserEntities = [];
        foreach ($departmentUsers as $departmentUser) {
            $departmentUserEntities[] = new DelightfulDepartmentUserEntity($departmentUser);
        }
        return $departmentUserEntities;
    }
}
