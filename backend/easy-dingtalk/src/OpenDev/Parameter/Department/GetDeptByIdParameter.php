<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Parameter\Department;

use Delightful\EasyDingTalk\Kernel\Exceptions\InvalidParameterException;
use Delightful\EasyDingTalk\OpenDev\Parameter\AbstractParameter;

class GetDeptByIdParameter extends AbstractParameter
{
    private int $deptId;

    public function getDeptId(): int
    {
        return $this->deptId;
    }

    public function setDeptId(int $deptId): void
    {
        $this->deptId = $deptId;
    }

    protected function validateParams(): void
    {
        if (empty($this->deptId)) {
            throw new InvalidParameterException('dept_id cannot be empty');
        }
    }
}
