<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

enum Type: int
{
    case None = 0;

    // 主process（直接use作助理）
    case Main = 1;

    // 子process
    case Sub = 2;

    // tool
    case Tools = 3;

    // group合sectionpoint，运linemethodhavepointanalogousat子process
    case CombinedNode = 4;

    // 循环sectionpoint
    case Loop = 5;

    public function needEndNode(): bool
    {
        return in_array($this, [self::Sub, self::Tools]);
    }

    public function canShowParams(): bool
    {
        return in_array($this, [self::Sub, self::Tools, self::CombinedNode]);
    }

    public function isMain(): bool
    {
        return $this === self::Main;
    }

    public function isSub(): bool
    {
        return $this === self::Sub;
    }

    public function isTools(): bool
    {
        return $this === self::Tools;
    }
}
