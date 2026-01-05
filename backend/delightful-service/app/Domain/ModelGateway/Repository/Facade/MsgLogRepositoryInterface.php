<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ModelGateway\Repository\Facade;

use App\Domain\ModelGateway\Entity\MsgLogEntity;
use App\Domain\ModelGateway\Entity\ValueObject\LLMDataIsolation;

interface MsgLogRepositoryInterface
{
    public function create(LLMDataIsolation $dataIsolation, MsgLogEntity $msgLogEntity): MsgLogEntity;
}
