<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

/**
 * frommessagemiddlegettextcontent,useat大modelhistorymessagerecordetcneed纯text的场景.
 */
interface TextContentInterface extends MessageInterface
{
    public function getTextContent(): string;
}
