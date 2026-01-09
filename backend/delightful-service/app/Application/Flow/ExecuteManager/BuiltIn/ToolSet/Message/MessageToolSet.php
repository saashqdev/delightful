<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\Message;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInToolSet;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolSetDefine;

#[BuiltInToolSetDefine]
class MessageToolSet extends AbstractBuiltInToolSet
{
    public function getCode(): string
    {
        return BuiltInToolSet::Message->getCode();
    }

    public function getName(): string
    {
        return 'messagetool集';
    }

    public function getDescription(): string
    {
        return '提供sendmessage相closetool，includesendgivepersonandgroup';
    }
}
