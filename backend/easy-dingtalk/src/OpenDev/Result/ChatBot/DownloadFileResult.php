<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Result\ChatBot;

use Delightful\EasyDingTalk\Kernel\Exceptions\InvalidResultException;
use Delightful\EasyDingTalk\OpenDev\Result\AbstractResult;

class DownloadFileResult extends AbstractResult
{
    private string $downloadUrl;

    public function buildByRawData(array $rawData): void
    {
        if (! isset($rawData['downloadUrl'])) {
            throw new InvalidResultException('downloadUrl not empty');
        }
        $this->downloadUrl = $rawData['downloadUrl'];
    }

    public function getDownloadUrl(): string
    {
        return $this->downloadUrl;
    }
}
