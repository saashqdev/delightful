<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Entity\ValueObject\Query;

use App\Infrastructure\Core\AbstractQuery;

/**
 * 子administratorcolumn表queryobject.
 *
 * useexample：
 * $query = new SubAdminQuery([
 *     'name' => 'rolename',
 *     'status' => 1,
 * ]);
 */
class SubAdminQuery extends AbstractQuery
{
    /** 子administratorname（blur匹配） */
    private ?string $name = null;

    /** enabled status：1-enable 0-disable */
    private ?int $status = null;

    /**
     * 构造functionsupportfromarray批quantityinitializeproperty（inherit自 AbstractObject）。
     */

    /* -------------------- getter / setter -------------------- */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * 将queryobjectconvert为仓储layer可识别的filterarray。
     */
    public function toFilters(): array
    {
        $filters = [];
        if ($this->name !== null && $this->name !== '') {
            $filters['name'] = $this->name;
        }
        if ($this->status !== null) {
            $filters['status'] = $this->status;
        }
        return $filters;
    }
}
