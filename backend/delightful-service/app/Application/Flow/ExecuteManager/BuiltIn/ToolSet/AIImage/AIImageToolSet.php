<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AIImage;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInToolSet;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolSetDefine;

#[BuiltInToolSetDefine]
class AIImageToolSet extends AbstractBuiltInToolSet
{
    public function getCode(): string
    {
        return BuiltInToolSet::AIImage->getCode();
    }

    public function getName(): string
    {
        return 'MageAI文生graphtool合集';
    }

    public function getDescription(): string
    {
        return 'MageAI文生graphtool合集';
    }
}
