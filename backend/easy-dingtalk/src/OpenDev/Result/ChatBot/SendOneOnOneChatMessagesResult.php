<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Result\ChatBot;

use Dtyq\EasyDingTalk\OpenDev\Result\AbstractResult;

class SendOneOnOneChatMessagesResult extends AbstractResult
{
    private string $processQueryKey;

    /**
     * @var array Invalid user userId list
     */
    private array $invalidStaffIdList = [];

    /**
     * @var array Flow-controlled userId list
     */
    private array $flowControlledStaffIdList = [];

    public function getProcessQueryKey(): string
    {
        return $this->processQueryKey;
    }

    public function getInvalidStaffIdList(): array
    {
        return $this->invalidStaffIdList;
    }

    public function getFlowControlledStaffIdList(): array
    {
        return $this->flowControlledStaffIdList;
    }

    public function buildByRawData(array $rawData): void
    {
        $this->processQueryKey = $rawData['processQueryKey'] ?? '';
        $this->invalidStaffIdList = $rawData['invalidStaffIdList'] ?? [];
        $this->flowControlledStaffIdList = $rawData['flowControlledStaffIdList'] ?? [];
    }
}
