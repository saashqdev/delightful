<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\PageResponseDTO;

use App\Domain\Contact\Entity\DelightfulDepartmentEntity;

class DepartmentsPageResponseDTO extends PageResponseDTO
{
    /**
     * @var DelightfulDepartmentEntity[]
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
