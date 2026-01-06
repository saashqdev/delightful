<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Event;

use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Entity\KnowledgeBaseFragmentEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeBaseDataIsolation;

class KnowledgeBaseFragmentSavedEvent
{
    public function __construct(
        public KnowledgeBaseDataIsolation $dataIsolation,
        public KnowledgeBaseEntity $delightfulFlowKnowledgeEntity,
        public KnowledgeBaseFragmentEntity $delightfulFlowKnowledgeFragmentEntity,
    ) {
    }
}
