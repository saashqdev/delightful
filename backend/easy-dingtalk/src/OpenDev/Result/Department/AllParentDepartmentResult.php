<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Result\Department;

use BeDelightful\EasyDingTalk\OpenDev\Result\AbstractResult;

class AllParentDepartmentResult extends AbstractResult
{
    private array $parentDeptIdList;

    public function buildByRawData(array $rawData): void
    {
        $this->parentDeptIdList = $rawData['parent_dept_id_list'] ?? [];
    }

    public function getParentDeptIdList(): array
    {
        return $this->parentDeptIdList;
    }

    public function currentDeptId(): ?int
    {
        $deptId = $this->parentDeptIdList[0] ?? null;
        if (! is_null($deptId)) {
            $deptId = (int) $deptId;
        }
        return $deptId;
    }
}
