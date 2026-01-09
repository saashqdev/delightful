<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\PageResponseDTO;

use App\Domain\Chat\Entity\DelightfulConversationEntity;

/**
 * paginationresponseDTO.
 */
class ConversationsPageResponseDTO extends PageResponseDTO
{
    /**
     * @var DelightfulConversationEntity[]
     */
    protected array $items = [];

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
