<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Contact\DTO;

use App\Infrastructure\Core\AbstractDTO;

class DelightfulUserOrganizationListDTO extends AbstractDTO
{
    /**
     * @var DelightfulUserOrganizationItemDTO[]
     */
    protected array $items = [];

    /**
     * @return DelightfulUserOrganizationItemDTO[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param DelightfulUserOrganizationItemDTO[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function addItem(DelightfulUserOrganizationItemDTO $item): void
    {
        $this->items[] = $item;
    }
}
