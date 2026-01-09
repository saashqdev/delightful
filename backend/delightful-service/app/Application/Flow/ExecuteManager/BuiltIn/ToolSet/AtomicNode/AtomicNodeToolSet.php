<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AtomicNode;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInToolSet;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolSetDefine;

#[BuiltInToolSetDefine]
class AtomicNodeToolSet extends AbstractBuiltInToolSet
{
    public function getCode(): string
    {
        return BuiltInToolSet::AtomicNode->getCode();
    }

    public function getName(): string
    {
        return '原子sectionpoint';
    }

    public function getDescription(): string
    {
        return '提供部minute原子sectionpointtoolcallcan力';
    }
}
