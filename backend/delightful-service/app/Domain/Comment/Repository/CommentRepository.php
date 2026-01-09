<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Comment\Repository;

use App\Domain\Comment\Entity\CommentEntity;
use App\Domain\Comment\Entity\VO\GetCommentsWhereVo;
use App\Domain\Comment\Repository\Model\CommentModel;
use App\Domain\Comment\Repository\Model\CommentTreeIndexModel;
use App\Infrastructure\Util\Context\RequestContext;
use Hyperf\Codec\Json;

class CommentRepository
{
    public function __construct(
        private TreeIndexRepository $treeIndexRepository,
    ) {
    }

    /**
     * create一个新的评论并维护相关的索引和附件。
     *
     * @param CommentEntity $commentEntity 评论实体
     * @return CommentEntity create后的评论实体
     */
    public function create(string $organizationCode, CommentEntity $commentEntity): CommentEntity
    {
        /** @var CommentModel $model */
        $model = CommentModel::query()->create([
            'type' => $commentEntity->getType(),
            'resource_id' => $commentEntity->getResourceId(),
            'resource_type' => $commentEntity->getResourceType(),
            'parent_id' => $commentEntity->getParentId(),
            'description' => $commentEntity->getDescription(),
            'message' => Json::encode($commentEntity->getMessage()),
            'creator' => $commentEntity->getCreator(),
            'organization_code' => $commentEntity->getOrganizationCode(),
            'attachments' => Json::encode($commentEntity->getAttachments()),
        ]);
        $commentEntity->setId($model->id);
        $commentEntity->setCreatedAt($model->created_at);
        $commentEntity->setUpdatedAt($model->updated_at);

        // 维护索引
        $this->treeIndexRepository->createIndexes(
            $organizationCode,
            CommentTreeIndexModel::query(),
            $commentEntity->getParentId(),
            $commentEntity->getId(),
        );

        return $commentEntity;
    }

    /**
     * update指定的评论content和附件。
     *
     * @param RequestContext $requestContext 请求上下文
     * @param CommentEntity $commentEntity 要update的评论实体
     */
    public function updateComment(
        RequestContext $requestContext,
        CommentEntity $commentEntity
    ): void {
        /** @var ?CommentModel $commentModel */
        $commentModel = CommentModel::query()->where('id', $commentEntity->getId())
            ->where('organization_code', $requestContext->getOrganizationCode())
            ->first();
        if (! $commentModel) {
            return;
        }

        if ($commentEntity->getMessage() !== null) {
            $commentModel->message = Json::encode($commentEntity->getMessage());
            $commentModel->save();
        }
    }

    /**
     * 根据条件get评论list。
     *
     * @param RequestContext $requestContext 请求上下文
     * @param GetCommentsWhereVo $whereVo query条件valueobject
     * @return array<CommentEntity> 评论实体array
     */
    public function getCommentsByConditions(
        RequestContext $requestContext,
        GetCommentsWhereVo $whereVo,
    ): array {
        $query = CommentModel::query()
            ->where('organization_code', $requestContext->getOrganizationCode());

        if ($whereVo->getIds()) {
            $query->whereIn('id', $whereVo->getIds());
        }

        /** @var CommentModel[] $commentModels */
        $commentModels = $query->get();
        return $this->modelsToEntities($commentModels);
    }

    /**
     * 根据评论IDarrayget对应的评论list。
     *
     * @param RequestContext $requestContext 请求上下文
     * @param array $commentIds 评论IDarray
     * @return array<CommentEntity> 评论实体array
     */
    public function getCommentsByIds(
        RequestContext $requestContext,
        array $commentIds
    ): array {
        $whereVo = new GetCommentsWhereVo();
        $whereVo->setIds($commentIds);
        return $this->getCommentsByConditions($requestContext, $whereVo);
    }

    /**
     * 根据评论IDget单个评论实体。
     *
     * @param RequestContext $requestContext 请求上下文
     * @param int $commentId 评论ID
     * @return ?CommentEntity 评论实体，如果不存在则returnnull
     */
    public function getCommentById(
        RequestContext $requestContext,
        int $commentId,
    ): ?CommentEntity {
        $whereVo = new GetCommentsWhereVo();
        $whereVo->setId($commentId);
        $whereVo->setIds([$commentId]);
        $commentEntities = $this->getCommentsByConditions(
            $requestContext,
            $whereVo
        );
        if (empty($commentEntities)) {
            return null;
        }

        return $commentEntities[$commentId];
    }

    /**
     * delete指定的评论。
     *
     * @param RequestContext $requestContext 请求上下文
     * @param int $commentId 评论ID
     * @return array delete的评论IDarray
     */
    public function delete(RequestContext $requestContext, int $commentId): array
    {
        return $this->batchDelete($requestContext, [$commentId]);
    }

    /**
     * 批量delete指定的评论及其所有子评论。
     *
     * @param RequestContext $requestContext 请求上下文
     * @param array $commentIds 要delete的评论IDarray
     * @return array delete的评论IDarray
     */
    public function batchDelete(
        RequestContext $requestContext,
        array $commentIds
    ): array {
        // get这条评论下的所有子评论
        $descendantIds = $this->treeIndexRepository->getDescendantIdsByAncestorIds(
            $requestContext,
            CommentTreeIndexModel::query(),
            $commentIds
        );

        $deletedCommentIds = array_unique([...$commentIds, ...$descendantIds]);

        // delete这条评论以及所有子评论
        CommentModel::query()->whereIn('id', $deletedCommentIds)
            ->where('organization_code', $requestContext->getOrganizationCode())
            ->delete();

        return $deletedCommentIds;
    }

    /**
     * 批量恢复已delete的评论。
     *
     * @param RequestContext $requestContext 请求上下文
     * @param array $commentIds 要恢复的评论IDarray
     */
    public function batchRestore(
        RequestContext $requestContext,
        array $commentIds
    ): void {
        CommentModel::withTrashed()->whereIn('id', $commentIds)
            ->where('organization_code', $requestContext->getOrganizationCode())
            ->restore();
    }

    /**
     * 根据资源IDget所有相关的评论。
     *
     * @param int $resourceId 资源ID
     * @return array<CommentEntity> 评论实体array
     */
    public function getCommentsByResourceId(string $organizationCode, int $resourceId): array
    {
        /** @var array<CommentModel> $results */
        $results = CommentModel::query()->where('resource_id', $resourceId)
            ->where('organization_code', $organizationCode)
            ->get();
        return $this->modelsToEntities($results);
    }

    /**
     * 根据query条件get评论list。
     *
     * @param RequestContext $requestContext 请求上下文
     * @param GetCommentsWhereVo $commentsWhereVo query条件valueobject
     * @return array<CommentEntity> 评论实体array
     */
    public function query(RequestContext $requestContext, GetCommentsWhereVo $commentsWhereVo): array
    {
        $model = CommentModel::query();
        if ($commentsWhereVo->getIds()) {
            $model->whereIn('id', $commentsWhereVo->getIds());
        }
        if ($commentsWhereVo->getId()) {
            $model->where('id', $commentsWhereVo->getId());
        }
        if ($commentsWhereVo->getResourceId()) {
            $model->where('resource_id', $commentsWhereVo->getResourceId());
        }
        if ($commentsWhereVo->getUseOrganizationCode()) {
            $organizationCode = $commentsWhereVo->getOrganizationCode() ?: $requestContext->getOrganizationCode();
            $model->where('organization_code', $organizationCode);
        }
        if ($commentsWhereVo->getOrganizationCode()) {
            $model->where('organization_code', $commentsWhereVo->getOrganizationCode());
        }
        if ($commentsWhereVo->getLastId()) {
            if ($commentsWhereVo->getLastDirection() === 'asc') {
                $model->where('id', '<', $commentsWhereVo->getLastId());
            } else {
                $model->where('id', '>', $commentsWhereVo->getLastId());
            }
        }
        if ($commentsWhereVo->getPageSize()) {
            $model->limit($commentsWhereVo->getPageSize());
        }
        if ($commentsWhereVo->getPage() && $commentsWhereVo->getPageSize()) {
            $model->forPage($commentsWhereVo->getPage(), $commentsWhereVo->getPageSize());
        }
        if ($commentsWhereVo->getSorts()) {
            foreach ($commentsWhereVo->getSorts() as $sort) {
                $model->orderBy($sort['key'], $sort['direction'])->orderBy('id', $sort['direction']);
            }
        } else {
            $model->orderBy('created_at', 'desc')->orderBy('id', 'desc');
        }
        $results = $model->get();
        return $this->modelsToEntities($results);
    }

    /**
     * 将CommentModel转换为CommentEntity。
     *
     * @param CommentModel $model 评论model
     * @return CommentEntity 转换后的评论实体
     */
    private function modelToEntity(CommentModel $model): CommentEntity
    {
        $commentEntity = new CommentEntity();
        $commentEntity->setId($model->id);
        $commentEntity->setType($model->type);
        $commentEntity->setResourceId($model->resource_id);
        $commentEntity->setResourceType($model->resource_type);
        $commentEntity->setParentId($model->parent_id);
        $commentEntity->setDescription($model->description);
        $commentEntity->setMessage(Json::decode($model->message));
        $commentEntity->setCreator($model->creator);
        $commentEntity->setOrganizationCode($model->organization_code);
        $commentEntity->setCreatedAt($model->created_at);
        $commentEntity->setUpdatedAt($model->updated_at);
        if ($model->attachments !== null) {
            $commentEntity->setAttachments(Json::decode($model->attachments));
        }

        return $commentEntity;
    }

    /**
     * 将多个CommentModel转换为CommentEntityarray。
     *
     * @param mixed $models 评论model集合
     * @return array<CommentEntity> 评论实体array
     */
    private function modelsToEntities(mixed $models): array
    {
        $commentEntities = [];
        /** @var CommentModel $model */
        foreach ($models as $model) {
            $commentEntities[$model->id] = $this->modelToEntity($model);
        }
        return $commentEntities;
    }
}
