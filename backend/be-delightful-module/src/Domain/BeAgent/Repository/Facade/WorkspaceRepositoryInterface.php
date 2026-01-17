<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\BeAgent\Entity\WorkspaceEntity;

interface WorkspaceRepositoryInterface
{
    /**
     * Get user workspace list.
     */
    public function getUserWorkspaces(string $userId, int $page, int $pageSize): array;

    /**
     * Create workspace.
     */
    public function createWorkspace(WorkspaceEntity $workspaceEntity): WorkspaceEntity;

    /**
     * Update workspace.
     */
    public function updateWorkspace(WorkspaceEntity $workspaceEntity): bool;

    /**
     * Get workspace details.
     */
    public function getWorkspaceById(int $workspaceId): ?WorkspaceEntity;

    /**
     * Find workspace by ID.
     */
    public function findById(int $workspaceId): ?WorkspaceEntity;

    /**
     * Get workspace by conversation ID.
     */
    public function getWorkspaceByConversationId(string $conversationId): ?WorkspaceEntity;

    /**
     * Update workspace archive status.
     */
    public function updateWorkspaceArchivedStatus(int $workspaceId, int $isArchived): bool;

    /**
     * Delete workspace.
     */
    public function deleteWorkspace(int $workspaceId): bool;

    /**
     * Delete topics associated with workspace.
     */
    public function deleteTopicsByWorkspaceId(int $workspaceId): bool;

    /**
     * Update workspace current topic.
     */
    public function updateWorkspaceCurrentTopic(int $workspaceId, string $topicId): bool;

    /**
     * Update workspace status.
     */
    public function updateWorkspaceStatus(int $workspaceId, int $status): bool;

    /**
     * Get workspace list by conditions
     * Supports pagination and sorting.
     *
     * @param array $conditions Query conditions
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @param string $orderBy Sort field
     * @param string $orderDirection Sort direction
     * @return array [total, list] Total count and workspace list
     */
    public function getWorkspacesByConditions(
        array $conditions = [],
        int $page = 1,
        int $pageSize = 10,
        string $orderBy = 'id',
        string $orderDirection = 'asc'
    ): array;

    /**
     * Save workspace (create or update).
     *
     * @param WorkspaceEntity $workspaceEntity Workspace entity
     * @return WorkspaceEntity Saved workspace entity
     */
    public function save(WorkspaceEntity $workspaceEntity): WorkspaceEntity;

    /**
     * Get unique organization code list from all workspaces.
     *
     * @return array Unique organization code list
     */
    public function getUniqueOrganizationCodes(): array;

    /**
     * Batch get workspace name mapping.
     *
     * @param array $workspaceIds Workspace ID array
     * @return array ['workspace_id' => 'workspace_name'] key-value pairs
     */
    public function getWorkspaceNamesBatch(array $workspaceIds): array;
}
