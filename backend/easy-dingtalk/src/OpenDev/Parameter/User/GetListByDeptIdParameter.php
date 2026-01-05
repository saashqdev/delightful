<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Parameter\User;

use Delightful\EasyDingTalk\Kernel\Exceptions\InvalidParameterException;
use Delightful\EasyDingTalk\OpenDev\Parameter\AbstractParameter;

class GetListByDeptIdParameter extends AbstractParameter
{
    private int $deptId;

    private int $cursor = 0;

    private int $size = 100;

    public function getDeptId(): int
    {
        return $this->deptId;
    }

    public function setDeptId(int $deptId): void
    {
        $this->deptId = $deptId;
    }

    public function getCursor(): int
    {
        return $this->cursor;
    }

    public function setCursor(int $cursor): void
    {
        $this->cursor = $cursor;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    protected function validateParams(): void
    {
        if (empty($this->deptId)) {
            throw new InvalidParameterException('dept_id cannot be empty');
        }
    }
}
