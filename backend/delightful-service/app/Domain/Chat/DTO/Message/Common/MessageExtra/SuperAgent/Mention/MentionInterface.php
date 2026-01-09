<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention;

use JsonSerializable;

/**
 * 通use Mention interface，所havementionobject均需implement。
 */
interface MentionInterface extends JsonSerializable
{
    /**
     * inmessage的 content 中 @了 file/mcp/tool etc.
     */
    public function getMentionTextStruct(): string;

    /**
     * get Mention object的 JSON 结构.
     */
    public function getMentionJsonStruct(): array;
}
