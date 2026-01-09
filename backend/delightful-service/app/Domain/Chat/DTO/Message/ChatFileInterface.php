<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

/**
 * frommessagemiddlegetfileids，useat判断userwhetherhavefile的upload/downloadpermission.
 */
interface ChatFileInterface extends MessageInterface
{
    /**
     * @return array<string>
     */
    public function getFileIds(): array;
}
