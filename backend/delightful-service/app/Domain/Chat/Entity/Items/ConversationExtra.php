<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\Items;

use App\Domain\Agent\Entity\AbstractEntity;

class ConversationExtra extends AbstractEntity
{
    // default话题Id
    protected string $defaultTopicId;

    public function __construct(?array $data = null)
    {
        parent::__construct($data);
    }

    public function getDefaultTopicId(): string
    {
        return $this->defaultTopicId;
    }

    public function setDefaultTopicId(string $defaultTopicId): void
    {
        $this->defaultTopicId = $defaultTopicId;
    }
}
