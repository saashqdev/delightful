<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Assembler;

use App\Domain\Flow\Entity\DelightfulFlowExecuteLogEntity;
use App\Interfaces\Flow\DTO\DelightfulFowExecuteResultDTO;

class DelightfulFlowExecuteLogAssembler
{
    public function createExecuteResultDTO(DelightfulFlowExecuteLogEntity $magicFlowExecuteLogEntity): DelightfulFowExecuteResultDTO
    {
        $executeResultDTO = new DelightfulFowExecuteResultDTO();
        $executeResultDTO->setTaskId((string) $magicFlowExecuteLogEntity->getId());
        $executeResultDTO->setStatus($magicFlowExecuteLogEntity->getStatus()->value);
        $executeResultDTO->setStatusLabel($magicFlowExecuteLogEntity->getStatus()->name);
        $executeResultDTO->setResult($magicFlowExecuteLogEntity->getResult());
        $executeResultDTO->setCreatedAt($magicFlowExecuteLogEntity->getCreatedAt()->format('Y-m-d H:i:s'));
        return $executeResultDTO;
    }
}
