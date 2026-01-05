<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Result\Calendar;

use Delightful\EasyDingTalk\Kernel\Exceptions\InvalidResultException;
use Delightful\EasyDingTalk\OpenDev\Result\AbstractResult;

class CreateEventResult extends AbstractResult
{
    private string $id;

    private string $summary;

    public function buildByRawData(array $rawData): void
    {
        if (empty($rawData['id'])) {
            throw new InvalidResultException('id cannot be empty');
        }
        if (empty($rawData['summary'])) {
            throw new InvalidResultException('summary cannot be empty');
        }
        $this->id = $rawData['id'];
        $this->summary = $rawData['summary'];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }
}
