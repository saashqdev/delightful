<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage;

use App\Domain\Chat\DTO\Message\DelightfulMessageStruct;
use App\Domain\Chat\DTO\Message\Trait\EditMessageOptionsTrait;

abstract class AbstractChatMessageStruct extends DelightfulMessageStruct
{
    use EditMessageOptionsTrait;
}
