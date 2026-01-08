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
    public function createExecuteResultDTO(DelightfulFlowExecuteLogEntity $delightfulFlowExecuteLogEntity): DelightfulFowExecuteResultDTO
    {
        $executeResultDTO = new DelightfulFowExecuteResultDTO();
        $executeResultDTO->setTaskId((string) $delightfulFlowExecuteLogEntity->getId());
        $executeResultDTO->setStatus($delightfulFlowExecuteLogEntity->getStatus()->value);
        $executeResultDTO->setStatusLabel($delightfulFlowExecuteLogEntity->getStatus()->name);
        $executeResultDTO->setResult($delightfulFlowExecuteLogEntity->getResult());
        $executeResultDTO->setCreatedAt($delightfulFlowExecuteLogEntity->getCreatedAt()->format('Y-m-d H:i:s'));
        return $executeResultDTO;
    }
}
