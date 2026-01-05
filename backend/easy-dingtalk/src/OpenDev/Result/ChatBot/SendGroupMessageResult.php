<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Result\ChatBot;

use Delightful\EasyDingTalk\OpenDev\Result\AbstractResult;

class SendGroupMessageResult extends AbstractResult
{
    private string $processQueryKey;

    public function getProcessQueryKey(): string
    {
        return $this->processQueryKey;
    }

    public function buildByRawData(array $rawData): void
    {
        $this->processQueryKey = $rawData['processQueryKey'] ?? '';
    }
}
