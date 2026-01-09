<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

enum Type: int
{
    case None = 0;

    // mainprocess(directlyuseasassistant)
    case Main = 1;

    // childprocess
    case Sub = 2;

    // tool
    case Tools = 3;

    // group合sectionpoint,runlinemethodhavepointanalogousatchildprocess
    case CombinedNode = 4;

    // loopsectionpoint
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
