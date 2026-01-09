<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

/**
 * frommessagemiddlegettextcontent,useatbigmodelhistorymessagerecordetcneed纯textscenario.
 */
interface TextContentInterface extends MessageInterface
{
    public function getTextContent(): string;
}
