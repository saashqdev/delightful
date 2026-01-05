<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Comment\Service;

use App\Domain\Comment\Entity\CommentEntity;
use App\Domain\Comment\Entity\VO\GetCommentsWhereVo;
use App\Domain\Comment\Repository\CommentRepository;
use App\Infrastructure\Util\Context\RequestContext;

class CommentDomainService
{
    public function __construct(private CommentRepository $commentRepository)
    {
    }

    /**
    * Create a new comment and maintain related indexes and attachments.
    *
    * @param CommentEntity $commentEntity Comment entity
    * @return CommentEntity Created comment entity
     */
    public function create(string $organizationCode, CommentEntity $commentEntity): CommentEntity
    {
        return $this->commentRepository->create($organizationCode, $commentEntity);
    }

    /**
    * Update comment content and attachments.
    *
    * @param RequestContext $requestContext Request context
    * @param CommentEntity $commentEntity Comment entity to update
     */
    public function updateComment(
        RequestContext $requestContext,
        CommentEntity $commentEntity
    ): void {
        $this->commentRepository->updateComment($requestContext, $commentEntity);
    }

    /**
    * Get comments by filter conditions.
    *
    * @param RequestContext $requestContext Request context
    * @param GetCommentsWhereVo $whereVo Filter value object
    * @return array<CommentEntity> Comment entities
     */
    public function getCommentsByConditions(
        RequestContext $requestContext,
        GetCommentsWhereVo $whereVo,
    ): array {
        return $this->commentRepository->getCommentsByConditions($requestContext, $whereVo);
    }

    /**
    * Get comments by an array of IDs.
    *
    * @param RequestContext $requestContext Request context
    * @param array $commentIds Comment ID list
    * @return array<CommentEntity> Comment entities
     */
    public function getCommentsByIds(
        RequestContext $requestContext,
        array $commentIds
    ): array {
        return $this->commentRepository->getCommentsByIds($requestContext, $commentIds);
    }

    /**
    * Get a single comment by ID.
    *
    * @param RequestContext $requestContext Request context
    * @param int $commentId Comment ID
    * @return ?CommentEntity Comment entity or null if missing
     */
    public function getCommentById(
        RequestContext $requestContext,
        int $commentId,
    ): ?CommentEntity {
        return $this->commentRepository->getCommentById($requestContext, $commentId);
    }

    /**
    * Delete a comment by ID.
    *
    * @param RequestContext $requestContext Request context
    * @param int $commentId Comment ID
    * @return array Deleted comment IDs
     */
    public function delete(RequestContext $requestContext, int $commentId): array
    {
        return $this->commentRepository->delete($requestContext, $commentId);
    }

    /**
    * Delete comments in batch and all their children.
    *
    * @param RequestContext $requestContext Request context
    * @param array $commentIds Comment IDs to delete
    * @return array Deleted comment IDs
     */
    public function batchDelete(
        RequestContext $requestContext,
        array $commentIds
    ): array {
        return $this->commentRepository->batchDelete($requestContext, $commentIds);
    }

    /**
    * Restore deleted comments in batch.
    *
    * @param RequestContext $requestContext Request context
    * @param array $commentIds Comment IDs to restore
     */
    public function batchRestore(
        RequestContext $requestContext,
        array $commentIds
    ): void {
        $this->commentRepository->batchRestore($requestContext, $commentIds);
    }

    /**
    * Get all comments for a resource ID.
    *
    * @param int $resourceId Resource ID
    * @return array<CommentEntity> Comment entities
     */
    public function getCommentsByResourceId(string $organizationCode, int $resourceId): array
    {
        return $this->commentRepository->getCommentsByResourceId($organizationCode, $resourceId);
    }

    /**
    * Query comments by conditions.
    *
    * @param RequestContext $requestContext Request context
    * @param GetCommentsWhereVo $commentsWhereVo Filter value object
    * @return array<CommentEntity> Comment entities
     */
    public function query(RequestContext $requestContext, GetCommentsWhereVo $commentsWhereVo): array
    {
        return $this->commentRepository->query($requestContext, $commentsWhereVo);
    }
}
