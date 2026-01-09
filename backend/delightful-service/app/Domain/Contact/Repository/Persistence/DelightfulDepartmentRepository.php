<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Persistence;

use App\Domain\Chat\DTO\PageResponseDTO\DepartmentsPageResponseDTO;
use App\Domain\Chat\Entity\ValueObject\PlatformRootDepartmentId;
use App\Domain\Contact\Entity\DelightfulDepartmentEntity;
use App\Domain\Contact\Entity\ValueObject\DepartmentOption;
use App\Domain\Contact\Repository\Facade\DelightfulDepartmentRepositoryInterface;
use App\Domain\Contact\Repository\Persistence\Model\DepartmentModel;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Interfaces\Chat\Assembler\DepartmentAssembler;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Log\LoggerInterface;
use Throwable;

class DelightfulDepartmentRepository implements DelightfulDepartmentRepositoryInterface
{
    public function __construct(
        protected DepartmentModel $model,
        protected Redis $redis,
        protected LockerInterface $locker,
        protected LoggerInterface $logger,
    ) {
        try {
            $this->logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get(get_class($this));
        } catch (Throwable) {
        }
    }

    public function getDepartmentById(string $departmentId, string $organizationCode): ?DelightfulDepartmentEntity
    {
        $department = $this->model->newQuery()
            ->where('organization_code', $organizationCode)
            ->where('department_id', $departmentId);
        $department = Db::select($department->toSql(), $department->getBindings())[0] ?? null;
        if (empty($department)) {
            return null;
        }
        return DepartmentAssembler::getDepartmentEntity($department);
    }

    /**
     * @return DelightfulDepartmentEntity[]
     */
    public function getDepartmentsByIds(array $departmentIds, string $organizationCode, bool $keyById = false): array
    {
        if (empty($departmentIds)) {
            return [];
        }
        $departments = $this->model->newQuery()
            ->where('organization_code', $organizationCode)
            ->whereIn('department_id', $departmentIds);
        $departments = Db::select($departments->toSql(), $departments->getBindings());
        return $this->getDepartmentsEntity($departments, $keyById);
    }

    /**
     * @return DelightfulDepartmentEntity[]
     */
    public function getDepartmentsByIdsInDelightful(array $departmentIds, bool $keyById = false): array
    {
        if (empty($departmentIds)) {
            return [];
        }
        $departments = $this->model->newQuery()
            ->whereIn('department_id', $departmentIds);
        $departments = Db::select($departments->toSql(), $departments->getBindings());
        return $this->getDepartmentsEntity($departments, $keyById);
    }

    public function getSubDepartmentsById(string $departmentId, string $organizationCode, int $size, int $offset): DepartmentsPageResponseDTO
    {
        $departments = $this->model->newQuery()
            ->where('organization_code', $organizationCode)
            ->where('parent_department_id', $departmentId)
            ->limit($size)
            ->offset($offset)
            ->orderBy('id');
        $departments = Db::select($departments->toSql(), $departments->getBindings());
        $items = $this->getDepartmentsEntity($departments);
        $hasMore = count($items) === $size;
        $pageToken = $hasMore ? (string) ($offset + $size) : '';
        return new DepartmentsPageResponseDTO([
            'items' => $items,
            'has_more' => $hasMore,
            'page_token' => $pageToken,
        ]);
    }

    /**
     * getsomeonelayerleveldepartment.
     */
    public function getSubDepartmentsByLevel(int $level, string $organizationCode, int $depth, int $size, int $offset): DepartmentsPageResponseDTO
    {
        $departments = $this->getSubDepartmentsByLevelCache($level, $organizationCode, $depth, $size, $offset);
        $delightfulDepartmentEntities = $this->getDepartmentsEntity($departments);
        // downonelevel子departmenthavenotcan预测quantity，thereforeas long asreturnquantityandlimitone致，then认forhavedownone页
        $hasMore = count($delightfulDepartmentEntities) === $size;
        $pageToken = $hasMore ? (string) ($offset + $size) : '';
        return new DepartmentsPageResponseDTO([
            'items' => $delightfulDepartmentEntities,
            'has_more' => $hasMore,
            'page_token' => $pageToken,
        ]);
    }

    // give定departmentidwhetherhavedownleveldepartment
    #[ArrayShape([
        'parent_department_id' => 'string',
    ])]
    public function hasChildDepartment(array $departmentIds, string $organizationCode): array
    {
        $query = $this->model->newQuery()
            ->select('parent_department_id')
            ->where('organization_code', $organizationCode)
            ->whereIn('parent_department_id', $departmentIds)
            ->groupBy(['parent_department_id']);
        return Db::select($query->toSql(), $query->getBindings());
    }

    public function getDepartmentByParentId(string $departmentId, string $organizationCode): ?DelightfulDepartmentEntity
    {
        // toatfront端come说, -1 table示rootdepartmentinfo.
        $query = $this->model->newQuery()->where('organization_code', $organizationCode);
        if ($departmentId === PlatformRootDepartmentId::Delightful) {
            $query->where(function (Builder $query) {
                $query->where('parent_department_id', '=', '')->orWhereNull('parent_department_id');
            });
        } else {
            $query->whereIn('parent_department_id', $departmentId);
        }
        $department = Db::select($query->toSql(), $query->getBindings())[0] ?? null;
        if (empty($department)) {
            return null;
        }
        return DepartmentAssembler::getDepartmentEntity($department);
    }

    /**
     * @return DelightfulDepartmentEntity[]
     */
    public function searchDepartments(string $departmentName, string $organizationCode, string $pageToken = '', ?int $pageSize = null): array
    {
        $departments = $this->model->newQuery()
            ->where('organization_code', $organizationCode)
            ->where('name', 'like', sprintf('%%%s%%', $departmentName))
            ->limit(100)
            /* @phpstan-ignore-next-line */
            ->when($pageSize, function (Builder $query) use ($pageToken, $pageSize) {
                // $pageToken forquery总quantity
                $page = ((int) ceil((int) $pageToken / $pageSize)) + 1;
                $query->forPage($page, $pageSize);
            });
        $departments = Db::select($departments->toSql(), $departments->getBindings());
        return $this->getDepartmentsEntity($departments);
    }

    /**
     * getorganization所havedepartment.
     * @return DelightfulDepartmentEntity[]
     */
    public function getOrganizationDepartments(string $organizationCode, array $fields = ['*'], bool $keyById = false): array
    {
        $departments = $this->getOrganizationDepartmentsRaw($organizationCode);
        return $this->getDepartmentsEntity($departments, $keyById);
    }

    public function addDepartmentDocument(string $departmentId, string $documentId): void
    {
        $this->model->newQuery()->where('department_id', $departmentId)->update(['document_id' => $documentId]);
    }

    /**
     * getdepartment所have子departmentmembertotal.
     * usefrom旋lock避免andhair，onetimepropertyquery所havedepartmentdataandcacheto Redis.
     */
    public function getSelfAndChildrenEmployeeSum(DelightfulDepartmentEntity $delightfulDepartmentEntity): int
    {
        $organizationCode = $delightfulDepartmentEntity->getOrganizationCode();
        $departmentId = $delightfulDepartmentEntity->getDepartmentId();

        // 先尝试from Redis cacheget
        $cacheKey = sprintf('department_employee_sum:%s', $organizationCode);

        $cachedData = $this->redis->hget($cacheKey, $departmentId);
        if ($cachedData !== false) {
            return (int) $cachedData;
        }

        // usefrom旋lock避免andhair计算
        $lockKey = sprintf('department_calc_lock:%s', $organizationCode);
        $lockOwner = uniqid('dept_calc_', true);

        if (! $this->locker->spinLock($lockKey, $lockOwner, 30)) {
            return 0;
        }

        try {
            // onetimepropertygetorganizationdown所havedepartmentdata
            $allDepartments = $this->getAllDepartmentsForCalculation($organizationCode);

            // 计算eachdepartment员工totalandcacheto Redis
            $this->calculateAndCacheAllDepartmentEmployeeSums($organizationCode, $allDepartments, $cacheKey);
            $result = $this->redis->hget($cacheKey, $departmentId);

            return $result !== false ? (int) $result : 0;
        } catch (Throwable $e) {
            $this->logger->error('Exception in getSelfAndChildrenEmployeeSum', [
                'organization_code' => $organizationCode,
                'department_id' => $departmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // hair生exceptiono clock直接计算not走cache
            return $this->calculateSelfAndChildrenEmployeeSum($organizationCode, $departmentId);
        } finally {
            $this->locker->release($lockKey, $lockOwner);
        }
    }

    /**
     * @param DelightfulDepartmentEntity[] $delightfulDepartmentsDTO
     * @return DelightfulDepartmentEntity[]
     */
    public function createDepartments(array $delightfulDepartmentsDTO): array
    {
        $time = date('Y-m-d H:i:s');
        $departments = [];
        foreach ($delightfulDepartmentsDTO as $delightfulDepartmentDTO) {
            if (empty($delightfulDepartmentDTO->getId())) {
                $department['id'] = (string) IdGenerator::getSnowId();
                $delightfulDepartmentDTO->setId($department['id']);
            }
            if (empty($delightfulDepartmentDTO->getCreatedAt())) {
                $delightfulDepartmentDTO->setCreatedAt($time);
            }
            if (empty($delightfulDepartmentDTO->getUpdatedAt())) {
                $delightfulDepartmentDTO->setUpdatedAt($time);
            }
            $department = $delightfulDepartmentDTO->toArray();
            unset($department['has_child']);
            $departments[] = $department;
        }
        $this->model::query()->insert($departments);
        return $delightfulDepartmentsDTO;
    }

    public function updateDepartment(string $departmentId, array $data, string $organizationCode): int
    {
        unset($data['has_child']);
        return $this->model->newQuery()
            ->where('department_id', $departmentId)
            ->where('organization_code', $organizationCode)
            ->update($data);
    }

    public function updateDepartmentsOptionByIds(array $departmentIds, ?DepartmentOption $departmentOption = null): int
    {
        if (empty($departmentIds)) {
            return 0;
        }
        $data['option'] = $departmentOption?->value;
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->model->newQuery()->whereIn('department_id', $departmentIds)->update($data);
    }

    /**
     * according todepartmentIDbatchquantitydeletedepartment（逻辑delete，set deleted_at field）。
     */
    public function deleteDepartmentsByIds(array $departmentIds, string $organizationCode): int
    {
        if (empty($departmentIds)) {
            return 0;
        }
        return $this->model->newQuery()
            ->where('organization_code', $organizationCode)
            ->whereIn('department_id', $departmentIds)
            ->delete();
    }

    public function getDepartmentRootId(string $organizationCode): ?string
    {
        $department = $this->model->newQuery()
            ->where('organization_code', $organizationCode)
            ->where('department_id', '=', PlatformRootDepartmentId::Delightful)
            ->first();

        return $department?->department_id;
    }

    /**
     * batchquantityget多organizationrootdepartmentinfo.
     * @param array $organizationCodes organizationcodearray
     * @return DelightfulDepartmentEntity[] rootdepartment实bodyarray
     */
    public function getOrganizationsRootDepartment(array $organizationCodes): array
    {
        if (empty($organizationCodes)) {
            return [];
        }

        $departments = $this->model->newQuery()
            ->whereIn('organization_code', $organizationCodes)
            ->where('department_id', '=', PlatformRootDepartmentId::Delightful);

        $departments = Db::select($departments->toSql(), $departments->getBindings());
        return $this->getDepartmentsEntity($departments);
    }

    /**
     * Get all organizations root departments with pagination support.
     * @param int $page Page number
     * @param int $pageSize Page size
     * @param string $organizationName Organization name for fuzzy search (optional)
     * @param array $organizationCodes Organization codes for exact match filter (optional)
     * @return array Array containing total and list
     */
    public function getAllOrganizationsRootDepartments(int $page = 1, int $pageSize = 20, string $organizationName = '', array $organizationCodes = []): array
    {
        $query = $this->model->newQuery()
            ->where('department_id', '=', PlatformRootDepartmentId::Delightful)
            ->where('parent_department_id', '-1');

        // Add organization codes exact match filter if provided
        if (! empty($organizationCodes)) {
            $query->whereIn('organization_code', $organizationCodes);
        }

        // Add organization name fuzzy search if provided
        if (! empty($organizationName)) {
            $query->where(function ($query) use ($organizationName) {
                $query->orWhere('name', 'like', sprintf('%%%s%%', $organizationName));
                $query->orWhere('organization_code', '=', $organizationName);
            });
        }

        // Get total count for pagination
        $totalQuery = clone $query;
        $total = $totalQuery->count();

        // Apply pagination
        $offset = ($page - 1) * $pageSize;
        $query->limit($pageSize)->offset($offset)->orderBy('created_at', 'desc');

        $departments = Db::select($query->toSql(), $query->getBindings());
        $list = $this->getDepartmentsEntity($departments);

        return [
            'total' => $total,
            'list' => $list,
        ];
    }

    /**
     * @return DelightfulDepartmentEntity[]
     */
    protected function getDepartmentsEntity(array $departments, bool $keyById = false): array
    {
        $departmentsEntity = [];
        foreach ($departments as $department) {
            $departmentEntity = DepartmentAssembler::getDepartmentEntity($department);
            if ($keyById) {
                $departmentsEntity[$departmentEntity->getDepartmentId()] = $departmentEntity;
            } else {
                $departmentsEntity[] = $departmentEntity;
            }
        }
        return $departmentsEntity;
    }

    #[Cacheable(prefix: 'get_organization_departments_raw', value: '#{organizationCode}', ttl: 60)]
    private function getOrganizationDepartmentsRaw(string $organizationCode): array
    {
        $departments = $this->model->newQuery()->where('organization_code', $organizationCode);
        return Db::select($departments->toSql(), $departments->getBindings());
    }

    #[Cacheable(prefix: 'getSubDepartmentsByLevel', ttl: 60)]
    private function getSubDepartmentsByLevelCache(int $currentDepartmentLevel, string $organizationCode, int $depth, int $size, int $offset): array
    {
        $minDepth = $currentDepartmentLevel + 1;
        $maxDepth = $currentDepartmentLevel + $depth;
        if ($minDepth > $maxDepth) {
            return [];
        }
        $query = $this->model->newQuery()
            ->where('organization_code', $organizationCode);
        if ($minDepth === $maxDepth) {
            $query->where('level', $minDepth);
        } else {
            $query->whereBetween('level', [$minDepth, $maxDepth]);
        }
        $query->limit($size)->offset($offset)->orderBy('id');
        return Db::select($query->toSql(), $query->getBindings());
    }

    /**
     * onetimepropertygetorganizationdown所havedepartmentdata，useat员工数计算.
     */
    private function getAllDepartmentsForCalculation(string $organizationCode): array
    {
        $query = $this->model::query()
            ->select(['department_id', 'parent_department_id', 'path', 'employee_sum', 'level'])
            ->where('organization_code', $organizationCode);

        return Db::select($query->toSql(), $query->getBindings());
    }

    /**
     * 计算andcache所havedepartment员工total.
     */
    private function calculateAndCacheAllDepartmentEmployeeSums(string $organizationCode, array $allDepartments, string $cacheKey): void
    {
        $departmentSums = [];

        // 1) initialize：eachdepartment先放入from身直属person数
        foreach ($allDepartments as $department) {
            $deptId = (string) $department['department_id'];
            $departmentSums[$deptId] = (int) ($department['employee_sum'] ?? 0);
        }

        // 2) frombottomtoup：按 level from大to小，子department累计valueaddto父department
        usort($allDepartments, static function (array $a, array $b): int {
            return (int) ($b['level'] ?? 0) <=> (int) ($a['level'] ?? 0);
        });

        foreach ($allDepartments as $department) {
            $deptId = (string) $department['department_id'];
            $parentId = (string) ($department['parent_department_id'] ?? '');

            if ($parentId === '' || $parentId === '-1') {
                continue; // skipno父levelorrootsectionpoint
            }

            $childSum = (int) ($departmentSums[$deptId] ?? 0);
            if ($childSum === 0) {
                continue;
            }

            if (! isset($departmentSums[$parentId])) {
                $departmentSums[$parentId] = 0;
            }
            $departmentSums[$parentId] += $childSum;
        }

        // batchquantitywrite Redis cache
        try {
            if (! empty($departmentSums)) {
                // ensure所havevalueallisstringformat
                $stringDepartmentSums = [];
                foreach ($departmentSums as $deptId => $sum) {
                    $stringDepartmentSums[$deptId] = (string) $sum;
                }
                $this->redis->multi();
                // use hmset onetimepropertyset多 hash field
                $this->redis->hmset($cacheKey, $stringDepartmentSums);
                // setcacheexpiretime
                $this->redis->expire($cacheKey, 60 * 5);
                $results = $this->redis->exec();

                // checktransactionexecuteresult
                if ($results === false) {
                    $this->logger->error('Redis transaction failed', [
                        'cache_key' => $cacheKey,
                        'organization_code' => $organizationCode,
                    ]);
                }
            } else {
                $this->logger->warning('departmentSums is empty, skipping Redis write', [
                    'organization_code' => $organizationCode,
                ]);
            }
        } catch (Throwable $e) {
            // Redis exceptiono clockrecordlog，butnot影响业务process
            $this->logger->warning('calculateAndCacheAllDepartmentEmployeeSums Failed to cache department employee sums', [
                'organization_code' => $organizationCode,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 直接计算singledepartment员工total（notusecache）.
     */
    private function calculateSelfAndChildrenEmployeeSum(string $organizationCode, string $departmentId): int
    {
        $query = $this->model->newQuery()
            ->select(['employee_sum'])
            ->where('organization_code', $organizationCode)
            ->where('path', 'like', sprintf('%%%s%%', $departmentId));

        $departments = Db::select($query->toSql(), $query->getBindings());

        $employeeSum = 0;
        foreach ($departments as $department) {
            $employeeSum += (int) ($department['employee_sum'] ?? 0);
        }

        return $employeeSum;
    }
}
