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
     * createonenewcommentand维护相closeindexandattachment。
     *
     * @param CommentEntity $commentEntity comment实body
     * @return CommentEntity createbackcomment实body
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

        // 维护index
        $this->treeIndexRepository->createIndexes(
            $organizationCode,
            CommentTreeIndexModel::query(),
            $commentEntity->getParentId(),
            $commentEntity->getId(),
        );

        return $commentEntity;
    }

    /**
     * updatefinger定commentcontentandattachment。
     *
     * @param RequestContext $requestContext requestupdown文
     * @param CommentEntity $commentEntity wantupdatecomment实body
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
     * according toitemitemgetcommentlist。
     *
     * @param RequestContext $requestContext requestupdown文
     * @param GetCommentsWhereVo $whereVo queryitemitemvalueobject
     * @return array<CommentEntity> comment实bodyarray
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
     * according tocommentIDarraygetto应commentlist。
     *
     * @param RequestContext $requestContext requestupdown文
     * @param array $commentIds commentIDarray
     * @return array<CommentEntity> comment实bodyarray
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
     * according tocommentIDgetsinglecomment实body。
     *
     * @param RequestContext $requestContext requestupdown文
     * @param int $commentId commentID
     * @return ?CommentEntity comment实body，ifnot存inthenreturnnull
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
     * deletefinger定comment。
     *
     * @param RequestContext $requestContext requestupdown文
     * @param int $commentId commentID
     * @return array deletecommentIDarray
     */
    public function delete(RequestContext $requestContext, int $commentId): array
    {
        return $this->batchDelete($requestContext, [$commentId]);
    }

    /**
     * batchquantitydeletefinger定commentandits所have子comment。
     *
     * @param RequestContext $requestContext requestupdown文
     * @param array $commentIds wantdeletecommentIDarray
     * @return array deletecommentIDarray
     */
    public function batchDelete(
        RequestContext $requestContext,
        array $commentIds
    ): array {
        // getthisitemcommentdown所have子comment
        $descendantIds = $this->treeIndexRepository->getDescendantIdsByAncestorIds(
            $requestContext,
            CommentTreeIndexModel::query(),
            $commentIds
        );

        $deletedCommentIds = array_unique([...$commentIds, ...$descendantIds]);

        // deletethisitemcommentbyand所have子comment
        CommentModel::query()->whereIn('id', $deletedCommentIds)
            ->where('organization_code', $requestContext->getOrganizationCode())
            ->delete();

        return $deletedCommentIds;
    }

    /**
     * batchquantityrestorealreadydeletecomment。
     *
     * @param RequestContext $requestContext requestupdown文
     * @param array $commentIds wantrestorecommentIDarray
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
     * according toresourceIDget所have相closecomment。
     *
     * @param int $resourceId resourceID
     * @return array<CommentEntity> comment实bodyarray
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
     * according toqueryitemitemgetcommentlist。
     *
     * @param RequestContext $requestContext requestupdown文
     * @param GetCommentsWhereVo $commentsWhereVo queryitemitemvalueobject
     * @return array<CommentEntity> comment实bodyarray
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
     * willCommentModelconvertforCommentEntity。
     *
     * @param CommentModel $model commentmodel
     * @return CommentEntity convertbackcomment实body
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
     * will多CommentModelconvertforCommentEntityarray。
     *
     * @param mixed $models commentmodelset
     * @return array<CommentEntity> comment实bodyarray
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
