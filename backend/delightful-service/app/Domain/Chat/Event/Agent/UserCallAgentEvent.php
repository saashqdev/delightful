<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Event\Agent;

use App\Domain\Chat\DTO\Agent\SenderExtraDTO;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Infrastructure\Core\AbstractEvent;

/**
 * user给agent发了message.
 */
class UserCallAgentEvent extends AbstractEvent
{
    public function __construct(
        public AccountEntity $agentAccountEntity,
        public DelightfulUserEntity $agentUserEntity,
        public AccountEntity $senderAccountEntity,
        public DelightfulUserEntity $senderUserEntity,
        public DelightfulSeqEntity $seqEntity,
        public ?DelightfulMessageEntity $messageEntity,
        public SenderExtraDTO $senderExtraDTO,
    ) {
    }
}
