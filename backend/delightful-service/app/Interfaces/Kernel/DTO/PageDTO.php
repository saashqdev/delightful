<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Kernel\DTO;

use App\Infrastructure\Core\AbstractDTO;

class PageDTO extends AbstractDTO
{
    public function __construct(public int $page, public int $total, public array $list)
    {
        $this->list = array_values($this->list);
        parent::__construct();
    }

    /**
     * get页码
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * gettotal.
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * get列tabledata.
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * setting页码
     */
    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    /**
     * settingtotal.
     */
    public function setTotal(int $total): self
    {
        $this->total = $total;
        return $this;
    }

    /**
     * setting列tabledata.
     */
    public function setList(array $list): self
    {
        $this->list = array_values($list);
        return $this;
    }
}
