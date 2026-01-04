<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Result\Department;

use Dtyq\EasyDingTalk\Kernel\Exceptions\InvalidResultException;
use Dtyq\EasyDingTalk\OpenDev\Result\AbstractResult;

class DeptResult extends AbstractResult
{
    private int $deptId;

    /**
     * When the address book is encrypted, this value cannot be retrieved and defaults to an empty string.
     */
    private string $name;

    private int $parentId;

    public function buildByRawData(array $rawData): void
    {
        if (! isset($rawData['dept_id'])) {
            throw new InvalidResultException('dept_id cannot be empty');
        }
        $this->deptId = (int) $rawData['dept_id'];
        $this->name = $rawData['name'] ?? '';
        $this->parentId = (int) ($rawData['parent_id'] ?? 0);
    }

    public function getDeptId(): int
    {
        return $this->deptId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }
}
