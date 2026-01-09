<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention;

use JsonSerializable;

/**
 * 通用 Mention 接口，所有提及object均需implement。
 */
interface MentionInterface extends JsonSerializable
{
    /**
     * 在message的 content 中 @了 文件/mcp/tool 等.
     */
    public function getMentionTextStruct(): string;

    /**
     * get Mention object的 JSON 结构.
     */
    public function getMentionJsonStruct(): array;
}
