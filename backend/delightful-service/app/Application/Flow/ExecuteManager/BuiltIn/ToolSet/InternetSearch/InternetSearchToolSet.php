<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\InternetSearch;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInToolSet;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolSetDefine;

#[BuiltInToolSetDefine]
class InternetSearchToolSet extends AbstractBuiltInToolSet
{
    public function getCode(): string
    {
        return BuiltInToolSet::InternetSearch->getCode();
    }

    public function getName(): string
    {
        return 'Mage互联网searchtool合集';
    }

    public function getDescription(): string
    {
        return 'Mage互联网searchtool合集';
    }
}
