<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\StreamMessage;

use App\Infrastructure\Core\AbstractObject;

class StepFinishedDTO extends AbstractObject
{
    /**
     * 大 json 的 key.
     */
    protected string $key;

    /**
     * endreason：
     * 0:processend
     * 1.发生exception.
     */
    protected FinishedReasonEnum $finishedReason;

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(int|string $key): self
    {
        $this->key = (string) $key;
        return $this;
    }

    public function getFinishedReason(): FinishedReasonEnum
    {
        return $this->finishedReason;
    }

    public function setFinishedReason(FinishedReasonEnum $finishedReason): self
    {
        $this->finishedReason = $finishedReason;
        return $this;
    }
}
