<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Repository\Facade;

use App\Domain\Flow\Entity\ValueObject\Query\KnowledgeBaseDocumentQuery;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseDocumentEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeBaseDataIsolation;
use App\Infrastructure\Core\ValueObject\Page;

/**
 * knowledge basedocument仓libraryinterface.
 */
interface KnowledgeBaseDocumentRepositoryInterface
{
    /**
     * createknowledge basedocument.
     */
    public function create(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentEntity $documentEntity): KnowledgeBaseDocumentEntity;

    /**
     * @param array<KnowledgeBaseDocumentEntity> $documentEntities
     */
    public function upsertByCode(KnowledgeBaseDataIsolation $dataIsolation, array $documentEntities): void;

    public function restoreOrCreate(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentEntity $documentEntity): KnowledgeBaseDocumentEntity;

    /**
     * updateknowledge basedocument.
     */
    public function update(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentEntity $documentEntity): KnowledgeBaseDocumentEntity;

    public function updateDocumentFile(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentEntity $documentEntity): int;

    public function updateWordCount(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeBaseCode, string $documentCode, int $deltaWordCount): void;

    /**
     * @return array array<knowledge basecode, documentquantity>
     */
    public function getDocumentCountByKnowledgeBaseCode(KnowledgeBaseDataIsolation $dataIsolation, array $knowledgeBaseCodes): array;

    /**
     * @return array<string, KnowledgeBaseDocumentEntity> array<documentcode, document名>
     */
    public function getDocumentsByCodes(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeBaseCode, array $knowledgeBaseDocumentCodes): array;

    /**
     * queryknowledge basedocument列表.
     *
     * @return array{total: int, list: array<KnowledgeBaseDocumentEntity>}
     */
    public function queries(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentQuery $query, Page $page): array;

    /**
     * pass第third-partyfileidqueryknowledge basedocument列表.
     *
     * @return array<KnowledgeBaseDocumentEntity>
     */
    public function getByThirdFileId(KnowledgeBaseDataIsolation $dataIsolation, string $thirdPlatformType, string $thirdFileId, ?string $knowledgeBaseCode = null, ?int $lastId = null, int $pageSize = 500): array;

    /**
     * 查看单个knowledge basedocumentdetail.
     */
    public function show(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeBaseCode, string $documentCode, bool $selectForUpdate = false): ?KnowledgeBaseDocumentEntity;

    /**
     * deleteknowledge basedocument.
     */
    public function destroy(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeBaseCode, string $documentCode): void;

    /**
     * according todocumentencodingdelete所有片段.
     */
    public function destroyFragmentsByDocumentCode(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeBaseCode, string $documentCode): void;

    public function changeSyncStatus(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentEntity $documentEntity): void;

    public function increaseVersion(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseDocumentEntity $documentEntity): int;
}
