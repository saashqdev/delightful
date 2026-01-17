<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Persistence;

use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectMemberEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberStatus;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberType;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\ProjectMemberRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\ProjectMemberModel;
use Hyperf\DbConnection\Db;

/**
 * Project member repository implementation.
 *
 * Responsible for data persistence operations of project members
 */
class ProjectMemberRepository implements ProjectMemberRepositoryInterface
{
    public function __construct(
        private readonly ProjectMemberModel $projectMemberModel
    ) {
    }

    /**
     * Batch insert project members.
     */
    public function insert(array $projectMemberEntities): void
    {
        if (empty($projectMemberEntities)) {
            return;
        }

        $attributes = $this->prepareBatchInsertAttributes($projectMemberEntities);

        // Use transaction to ensure data consistency
        Db::transaction(function () use ($attributes) {
            // Batch insert to avoid inserting too much data at once
            $chunks = array_chunk($attributes, 100);
            foreach ($chunks as $chunk) {
                $this->projectMemberModel::query()->insert($chunk);
            }
        });
    }

    /**
     * Delete all members by project ID.
     */
    public function deleteByProjectId(int $projectId, array $roles = []): int
    {
        $query = $this->projectMemberModel::query();
        if (! empty($roles)) {
            $query = $query->whereIn('role', $roles);
        }
        return $query->where('project_id', $projectId)->delete();
    }

    /**
     * Batch delete members by ID array.
     */
    public function deleteByIds(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        return $this->projectMemberModel::query()
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * Delete member relationship for specified project and user.
     */
    public function deleteByProjectAndUser(int $projectId, string $userId): int
    {
        return $this->projectMemberModel::query()
            ->where('project_id', $projectId)
            ->where('target_type', 'User')
            ->where('target_id', $userId)
            ->delete();
    }

    /**
     * Delete member relationship for specified project and target.
     */
    public function deleteByProjectAndTarget(int $projectId, string $targetType, string $targetId): int
    {
        return $this->projectMemberModel::query()
            ->where('project_id', $projectId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->delete();
    }

    /**
     * Check if member relationship exists for project and user.
     */
    public function existsByProjectAndUser(int $projectId, string $userId): bool
    {
        return $this->projectMemberModel::query()
            ->where('project_id', $projectId)
            ->where('target_type', MemberType::USER->value)
            ->where('target_id', $userId)
            ->exists();
    }

    /**
     * Check if member relationship exists for project and department list.
     */
    public function existsByProjectAndDepartments(int $projectId, array $departmentIds): bool
    {
        if (empty($departmentIds)) {
            return false;
        }

        return $this->projectMemberModel::query()
            ->where('project_id', $projectId)
            ->where('target_type', MemberType::DEPARTMENT->value)
            ->whereIn('target_id', $departmentIds)
            ->exists();
    }

    /**
     * Get all project members by project ID.
     *
     * @param int $projectId Project ID
     * @param array $roles Member roles
     * @return ProjectMemberEntity[] Project member entity array
     */
    public function findByProjectId(int $projectId, array $roles = []): array
    {
        $query = $this->projectMemberModel::query()->where('project_id', $projectId);
        if (! empty($roles)) {
            $query->whereIn('role', $roles);
        }
        $results = $query->orderBy('id', 'asc')->get()->toArray();

        $entities = [];
        foreach ($results as $row) {
            $entities[] = ProjectMemberEntity::modelToEntity($row);
        }

        return $entities;
    }

    /**
     * Get project ID list and total by user and departments (supports pinned sorting).
     */
    public function getProjectIdsByUserAndDepartments(
        string $userId,
        array $departmentIds = [],
        ?string $name = null,
        ?string $sortField = null,
        string $sortDirection = 'desc',
        array $creatorUserIds = [],
        ?string $joinMethod = null,
        array $organizationCodes = []
    ): array {
        $query = $this->projectMemberModel::query()
            ->where(function ($query) use ($userId, $departmentIds) {
                $query->where(function ($subQuery) use ($userId) {
                    $subQuery->where('target_type', MemberType::USER->value)
                        ->where('target_id', $userId);
                });

                if (! empty($departmentIds)) {
                    $query->orWhere(function ($subQuery) use ($departmentIds) {
                        $subQuery->where('target_type', MemberType::DEPARTMENT->value)
                            ->whereIn('target_id', $departmentIds);
                    });
                }
            });

        $query->join('delightful_be_agent_project', 'delightful_be_agent_project_members.project_id', '=', 'delightful_be_agent_project.id')
            ->leftJoin('delightful_be_agent_project_member_settings', function ($join) use ($userId) {
                $join->on('delightful_be_agent_project_member_settings.project_id', '=', 'delightful_be_agent_project.id')
                    ->where('delightful_be_agent_project_member_settings.user_id', '=', $userId);
            })
            ->where('delightful_be_agent_project.user_id', '!=', $userId)
            ->where('delightful_be_agent_project.is_collaboration_enabled', 1)
            ->whereNull('delightful_be_agent_project.deleted_at');

        if (! empty($name)) {
            // If project name search condition exists, need to join project table
            $query->where('delightful_be_agent_project.project_name', 'like', '%' . $name . '%');
        }

        if (! empty($creatorUserIds)) {
            // If creator user ID search condition exists
            $query->whereIn('delightful_be_agent_project.user_id', $creatorUserIds);
        }

        if (! empty($joinMethod)) {
            // Join method
            $query->where('delightful_be_agent_project_members.join_method', $joinMethod);
        }

        if (! empty($organizationCodes)) {
            // Filter by organization code
            $query->whereIn('delightful_be_agent_project.user_organization_code', $organizationCodes);
        }

        $query->select(
            'delightful_be_agent_project_members.project_id',
            'delightful_be_agent_project.updated_at',
            'delightful_be_agent_project.created_at',
            'delightful_be_agent_project_member_settings.is_pinned',
            'delightful_be_agent_project_member_settings.last_active_at',
            'delightful_be_agent_project_member_settings.is_bind_workspace',
            'delightful_be_agent_project_member_settings.bind_workspace_id'
        )
            ->distinct()
            ->orderByRaw('COALESCE(delightful_be_agent_project_member_settings.is_pinned, 0) DESC'); // Pinned first

        // Sort by sort field (default sort by updated_at)
        $effectiveSortField = $sortField ?: 'updated_at';
        $effectiveSortDirection = $sortDirection ?: 'desc';

        switch ($effectiveSortField) {
            case 'updated_at':
                $query->orderBy('delightful_be_agent_project.updated_at', $effectiveSortDirection);
                break;
            case 'created_at':
                $query->orderBy('delightful_be_agent_project.created_at', $effectiveSortDirection);
                break;
            case 'last_active_at':
                $query->orderBy('delightful_be_agent_project_member_settings.last_active_at', $effectiveSortDirection);
                break;
            default:
                $query->orderBy('delightful_be_agent_project.updated_at', 'desc');
                break;
        }

        $results = $query->get()->toArray();

        return [
            'total' => count($results),
            'list' => $results,
        ];
    }

    /**
     * Batch get project members count.
     *
     * @param array $projectIds Project ID array
     * @return array [project_id => total_count]
     */
    public function getProjectMembersCounts(array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        // Optimize N+1 problem with single query
        $results = $this->projectMemberModel::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('role', [MemberRole::MANAGE->value, MemberRole::EDITOR->value, MemberRole::VIEWER->value])
            ->groupBy('project_id')
            ->selectRaw('project_id, COUNT(*) as total_count')
            ->get()
            ->keyBy('project_id')
            ->toArray();

        // Ensure all project IDs have return value, projects without members return 0
        $counts = [];
        foreach ($projectIds as $projectId) {
            $counts[$projectId] = (int) ($results[$projectId]['total_count'] ?? 0);
        }

        return $counts;
    }

    /**
     * Batch get preview of first N members for projects.
     *
     * @param array $projectIds Project ID array
     * @param int $limit Limit number, default 4
     * @return array [project_id => [ProjectMemberEntity[], ...]]
     */
    public function getProjectMembersPreview(array $projectIds, int $limit = 4): array
    {
        if (empty($projectIds)) {
            return [];
        }

        // Use Eloquent query to batch get all related project members
        $results = $this->projectMemberModel::query()
            ->whereIn('project_id', $projectIds)
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();

        // Initialize result array
        $preview = [];
        foreach ($projectIds as $projectId) {
            $preview[$projectId] = [];
        }

        // Group by project and limit number of members per project
        $memberCounts = [];
        foreach ($results as $member) {
            $projectId = $member['project_id'];

            // Initialize counter
            if (! isset($memberCounts[$projectId])) {
                $memberCounts[$projectId] = 0;
            }

            // Add to result if limit not reached
            if ($memberCounts[$projectId] < $limit) {
                $preview[$projectId][] = ProjectMemberEntity::modelToEntity($member);
                ++$memberCounts[$projectId];
            }
        }

        return $preview;
    }

    /**
     * Get project ID list and total created by user with members (supports pinned sorting).
     */
    public function getSharedProjectIdsByUser(
        string $userId,
        string $organizationCode,
        ?string $name = null,
        int $page = 1,
        int $pageSize = 10,
        ?string $sortField = null,
        string $sortDirection = 'desc',
        array $creatorUserIds = []
    ): array {
        // Build base query: find projects created by user with members
        $query = $this->projectMemberModel::query()
            ->join('delightful_be_agent_project', 'delightful_be_agent_project_members.project_id', '=', 'delightful_be_agent_project.id')
            ->leftJoin('delightful_be_agent_project_member_settings', function ($join) use ($userId) {
                $join->on('delightful_be_agent_project_member_settings.project_id', '=', 'delightful_be_agent_project.id')
                    ->where('delightful_be_agent_project_member_settings.user_id', '=', $userId);
            })
            ->whereIn('delightful_be_agent_project_members.role', [MemberRole::MANAGE->value, MemberRole::EDITOR->value, MemberRole::VIEWER->value])
            ->where('delightful_be_agent_project.user_id', '=', $userId)
            ->where('delightful_be_agent_project.user_organization_code', '=', $organizationCode)
            ->where('delightful_be_agent_project.is_collaboration_enabled', 1)
            ->whereNull('delightful_be_agent_project.deleted_at');

        // If project name search condition exists
        if (! empty($name)) {
            $query->where('delightful_be_agent_project.project_name', 'like', '%' . $name . '%');
        }

        // If creator user ID search condition exists (for shared type, usually projects created by current user, mainly for interface consistency)
        if (! empty($creatorUserIds)) {
            $query->whereIn('delightful_be_agent_project.user_id', $creatorUserIds);
        }

        // Get total
        $totalQuery = clone $query;
        $total = $totalQuery->select('delightful_be_agent_project_members.project_id')->distinct()->count();

        // Paginated query for project IDs (including sort fields for DISTINCT compatibility)
        $projects = $query->select(
            'delightful_be_agent_project_members.project_id',
            'delightful_be_agent_project.updated_at',
            'delightful_be_agent_project.created_at',
            'delightful_be_agent_project_member_settings.is_pinned',
            'delightful_be_agent_project_member_settings.last_active_at',
            'delightful_be_agent_project_member_settings.is_bind_workspace',
            'delightful_be_agent_project_member_settings.bind_workspace_id'
        )
            ->distinct()
            ->orderByRaw('COALESCE(delightful_be_agent_project_member_settings.is_pinned, 0) DESC'); // Pinned first

        // Sort by sort field (default sort by updated_at)
        $effectiveSortField = $sortField ?: 'updated_at';
        $effectiveSortDirection = $sortDirection ?: 'desc';

        switch ($effectiveSortField) {
            case 'updated_at':
                $projects->orderBy('delightful_be_agent_project.updated_at', $effectiveSortDirection);
                break;
            case 'created_at':
                $projects->orderBy('delightful_be_agent_project.created_at', $effectiveSortDirection);
                break;
            case 'last_active_at':
                $projects->orderBy('delightful_be_agent_project_member_settings.last_active_at', $effectiveSortDirection);
                break;
            default:
                $projects->orderBy('delightful_be_agent_project.updated_at', 'desc');
                break;
        }

        $projects = $projects->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get()
            ->toArray();

        return [
            'total' => $total,
            'list' => $projects,
        ];
    }

    /**
     * Get creator user ID list for collaboration projects.
     */
    public function getCollaborationProjectCreatorIds(
        string $userId,
        array $departmentIds,
        string $organizationCode
    ): array {
        $query = $this->projectMemberModel::query()
            ->leftJoin('delightful_be_agent_project as projects', 'delightful_be_agent_project_members.project_id', '=', 'projects.id')
            ->where('delightful_be_agent_project_members.organization_code', $organizationCode);

        // Build user permission query condition - user is project member or department member
        $query->where(function ($q) use ($userId, $departmentIds) {
            $q->where(function ($userQuery) use ($userId) {
                $userQuery->where('delightful_be_agent_project_members.target_type', 'User')
                    ->where('delightful_be_agent_project_members.target_id', $userId);
            });

            if (! empty($departmentIds)) {
                $q->orWhere(function ($deptQuery) use ($departmentIds) {
                    $deptQuery->where('delightful_be_agent_project_members.target_type', 'Department')
                        ->whereIn('delightful_be_agent_project_members.target_id', $departmentIds);
                });
            }
        });

        // Only get project members with normal status
        $query->where('delightful_be_agent_project_members.status', '1');

        // Group by creator ID to deduplicate, get unique creator ID list
        $creatorIds = $query->select('projects.user_id')
            ->whereNotNull('projects.user_id')
            ->groupBy('projects.user_id')
            ->pluck('projects.user_id')
            ->toArray();

        return array_filter($creatorIds); // Filter empty values
    }

    /**
     * Get list of projects participated by user (supports collaboration project filtering and workspace binding filtering).
     */
    public function getParticipatedProjects(
        string $userId,
        ?int $workspaceId,
        bool $showCollaboration = true,
        ?string $projectName = null,
        int $page = 1,
        int $pageSize = 10,
        string $sortField = 'last_active_at',
        string $sortDirection = 'desc',
        ?array $organizationCodes = null
    ): array {
        // Build base query
        $query = $this->projectMemberModel::query()
            ->from('delightful_be_agent_project_members as pm')
            ->join('delightful_be_agent_project as p', 'pm.project_id', '=', 'p.id')
            ->leftJoin('delightful_be_agent_project_member_settings as pms', function ($join) use ($userId) {
                $join->on('p.id', '=', 'pms.project_id')
                    ->where('pms.user_id', '=', $userId);
            })
            ->where('pm.target_type', MemberType::USER->value)
            ->where('pm.target_id', $userId)
            ->where('pm.status', 1)
            ->where('p.project_status', 1) // Only query active projects
            ->whereNull('p.deleted_at');

        // Workspace restriction (optional)
        if ($workspaceId !== null) {
            $query->where(function ($subQuery) use ($workspaceId, $userId) {
                $subQuery
                    // Case 1: Projects created by user in specified workspace
                    ->where(function ($q) use ($workspaceId, $userId) {
                        $q->where('p.user_id', $userId)
                            ->where('p.workspace_id', $workspaceId);
                    })
                    // Case 2: Collaboration projects, user bound to specified workspace
                    ->orWhere(function ($q) use ($workspaceId, $userId) {
                        $q->where('p.user_id', '!=', $userId)
                            ->where('pms.is_bind_workspace', 1)
                            ->where('pms.bind_workspace_id', $workspaceId);
                    });
            });
        }

        // Project name fuzzy search
        if (! empty($projectName)) {
            $query->where('p.project_name', 'like', '%' . $projectName . '%');
        }

        // Organization filter
        if (! empty($organizationCodes)) {
            $query->whereIn('p.user_organization_code', $organizationCodes);
        }

        // Collaboration project filtering logic
        if (! $showCollaboration) {
            // When showCollaboration = 0, only show projects with shortcuts set
            $query->where('pms.is_bind_workspace', 1);

            // If workspace specified, further restrict to projects bound to that workspace
            if ($workspaceId !== null) {
                $query->where('pms.bind_workspace_id', $workspaceId);
            }
        }
        // When showCollaboration = 1, show all participated projects (default case)

        // Get total
        $totalQuery = clone $query;
        $total = $totalQuery->select('p.id')->distinct()->count();

        // Build query fields
        $query->select([
            'p.id',
            'p.workspace_id',
            'p.project_name',
            'p.project_description',
            'p.work_dir',
            'p.current_topic_id',
            'p.current_topic_status',
            'p.project_status',
            'p.project_mode',
            'p.user_id',
            'p.user_organization_code as organization_code',
            'p.is_collaboration_enabled',
            'p.default_join_permission',
            'p.created_at',
            'p.updated_at',
            'pm.role as user_role',
            Db::raw('COALESCE(pms.is_pinned, 0) as is_pinned'),
            Db::raw('COALESCE(pms.is_bind_workspace, 0) as is_bind_workspace'),
            Db::raw('COALESCE(pms.bind_workspace_id, 0) as bind_workspace_id'),
            Db::raw('COALESCE(pms.last_active_at, p.created_at) as last_active_at'),
            'pms.pinned_at',
            Db::raw('CASE WHEN p.user_id != ? THEN 1 ELSE 0 END as is_collaborator'),
        ])
            ->addBinding($userId, 'select');

        // Deduplicate
        $query->distinct();

        // Sorting logic: pinned first, pinned projects sorted by pinned time, finally sorted by active time
        $query->orderByRaw('COALESCE(pms.is_pinned, 0) DESC'); // 1. Pinned first
        $query->orderByRaw('pms.pinned_at DESC'); // 2. Pinned projects sorted by pinned time (last pinned first)
        $query->orderByRaw('COALESCE(pms.last_active_at, p.created_at) DESC'); // 3. Sorted by active time

        // Pagination
        $offset = ($page - 1) * $pageSize;
        $projects = $query->offset($offset)
            ->limit($pageSize)
            ->get()
            ->toArray();

        return [
            'total' => $total,
            'list' => $projects,
        ];
    }

    /**
     * Get project member information by project ID and user ID.
     */
    public function getMemberByProjectAndUser(int $projectId, string $userId): ?ProjectMemberEntity
    {
        $memberData = $this->projectMemberModel::query()
            ->where('project_id', $projectId)
            ->where('target_type', MemberType::USER->value)
            ->where('target_id', $userId)
            ->first();

        if (! $memberData) {
            return null;
        }

        return $this->toEntity($memberData->toArray());
    }

    /**
     * Get member list by project ID and member ID array.
     */
    public function getMembersByIds(int $projectId, array $memberIds): array
    {
        if (empty($memberIds)) {
            return [];
        }

        $membersData = $this->projectMemberModel::query()
            ->where('project_id', $projectId)
            ->whereIn('target_id', $memberIds)
            ->get();

        $entities = [];
        foreach ($membersData as $memberData) {
            $entities[] = $this->toEntity($memberData->toArray());
        }

        return $entities;
    }

    /**
     * Get project member list by project ID and department ID array.
     */
    public function getMembersByProjectAndDepartmentIds(int $projectId, array $departmentIds): array
    {
        if (empty($departmentIds)) {
            return [];
        }

        $membersData = $this->projectMemberModel::query()
            ->where('project_id', $projectId)
            ->where('target_type', 'Department')
            ->whereIn('target_id', $departmentIds)
            ->get();

        $entities = [];
        foreach ($membersData as $memberData) {
            $entities[] = $this->toEntity($memberData->toArray());
        }

        return $entities;
    }

    /**
     * Batch update member permissions (new format: target_type + target_id).
     */
    public function batchUpdateRole(int $projectId, array $roleUpdates): int
    {
        if (empty($roleUpdates)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $updatedCount = 0;

        foreach ($roleUpdates as $update) {
            $targetType = $update['target_type'];
            $targetId = $update['target_id'];
            $role = $update['role'];

            $result = $this->projectMemberModel::query()
                ->where('project_id', $projectId)
                ->where('target_type', $targetType)
                ->where('target_id', $targetId)
                ->update([
                    'role' => $role,
                    'updated_at' => $now,
                ]);

            $updatedCount += $result;
        }

        return $updatedCount;
    }

    /**
     * Batch delete members (hard delete).
     */
    public function deleteMembersByIds(int $projectId, array $memberIds): int
    {
        if (empty($memberIds)) {
            return 0;
        }

        // Use hard delete because table has no deleted_at field
        return $this->projectMemberModel::query()
            ->where('project_id', $projectId)
            ->whereIn('target_id', $memberIds)
            ->delete();
    }

    /**
     * Get project IDs list by collaborator target IDs (exclude OWNER role).
     */
    public function getProjectIdsByCollaboratorTargets(array $targetIds, array $roles): array
    {
        if (empty($targetIds)) {
            return [];
        }

        return $this->projectMemberModel::query()
            ->whereIn('target_id', $targetIds)
            ->whereIn('role', $roles)
            ->where('status', MemberStatus::ACTIVE->value)
            ->distinct()
            ->pluck('project_id')
            ->toArray();
    }

    /**
     * Batch get user's member records in projects.
     */
    public function getProjectMembersByTargetIds(array $projectIds, array $targetIds): array
    {
        if (empty($projectIds) || empty($targetIds)) {
            return [];
        }

        $results = $this->projectMemberModel::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('target_id', $targetIds)
            ->where('status', MemberStatus::ACTIVE->value)
            ->get()
            ->toArray();

        $entities = [];
        foreach ($results as $row) {
            $entities[] = ProjectMemberEntity::modelToEntity($row);
        }

        return $entities;
    }

    /**
     * Prepare attribute array for batch insert.
     */
    private function prepareBatchInsertAttributes(array $projectMemberEntities): array
    {
        $attributes = [];

        foreach ($projectMemberEntities as $entity) {
            $memberAttrs = $this->entityToModelAttributes($entity);

            if ($entity->getId() === 0) {
                $snowId = IdGenerator::getSnowId();
                $memberAttrs['id'] = $snowId;
                $entity->setId($snowId);
            }

            $attributes[] = $memberAttrs;
        }

        return $attributes;
    }

    /**
     * Convert entity to model attributes.
     */
    private function entityToModelAttributes(ProjectMemberEntity $entity): array
    {
        $now = date('Y-m-d H:i:s');

        return [
            'id' => $entity->getId(),
            'project_id' => $entity->getProjectId(),
            'target_type' => $entity->getTargetType()->value,
            'target_id' => $entity->getTargetId(),
            'role' => $entity->getRole()->value,
            'organization_code' => $entity->getOrganizationCode(),
            'status' => $entity->getStatus()->value,
            'invited_by' => $entity->getInvitedBy(),
            'join_method' => $entity->getJoinMethod()->value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * Convert data array to ProjectMemberEntity.
     */
    private function toEntity(array $data): ProjectMemberEntity
    {
        // Handle type conversion for target_type field
        $targetType = $data['target_type'] ?? MemberType::USER->value;
        if (is_string($targetType)) {
            $targetType = MemberType::from($targetType);
        }

        // Handle type conversion for role field
        $role = $data['role'] ?? MemberRole::EDITOR->value;
        if (is_string($role)) {
            $role = MemberRole::from($role);
        }

        // Handle type conversion for status field
        $status = $data['status'] ?? MemberStatus::ACTIVE->value;
        if (is_int($status)) {
            $status = MemberStatus::from($status);
        }

        return new ProjectMemberEntity([
            'id' => $data['id'] ?? 0,
            'project_id' => $data['project_id'] ?? 0,
            'target_type' => $targetType,
            'target_id' => $data['target_id'] ?? '',
            'role' => $role,
            'organization_code' => $data['organization_code'] ?? '',
            'status' => $status,
            'invited_by' => $data['invited_by'] ?? '',
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
            'deleted_at' => $data['deleted_at'] ?? null,
        ]);
    }
}
