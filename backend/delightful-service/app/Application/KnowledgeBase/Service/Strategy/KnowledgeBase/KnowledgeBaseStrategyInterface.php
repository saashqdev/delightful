<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\KnowledgeBase\Service\Strategy\KnowledgeBase;

use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeBaseDataIsolation;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\Operation;

interface KnowledgeBaseStrategyInterface
{
    public function getKnowledgeBaseOperations(KnowledgeBaseDataIsolation $dataIsolation): array;

    public function getQueryKnowledgeTypes(): array;

    public function getKnowledgeOperation(KnowledgeBaseDataIsolation $dataIsolation, int|string $knowledgeCode): Operation;

    public function getOrCreateDefaultDocument(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $knowledgeBaseEntity): void;

    /**
     * get或createdefaultknowledge basedata源type.
     *
     * @param KnowledgeBaseEntity $knowledgeBaseEntity knowledge base实体
     *
     * @return null|int data源type
     */
    public function getOrCreateDefaultSourceType(KnowledgeBaseEntity $knowledgeBaseEntity): ?int;
}
