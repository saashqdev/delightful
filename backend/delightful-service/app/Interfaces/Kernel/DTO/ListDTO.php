<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Kernel\DTO;

use App\Infrastructure\Core\AbstractDTO;

class ListDTO extends AbstractDTO
{
    public function __construct(public array $list)
    {
        $this->list = array_values($this->list);
        parent::__construct();
    }

    /**
     * getcolumntabledata.
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * settingcolumntabledata.
     */
    public function setList(array $list): self
    {
        $this->list = array_values($list);
        return $this;
    }
}
