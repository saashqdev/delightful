<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Result;

abstract class AbstractResult
{
    /**
     * Raw data.
     */
    private array $rawData;

    public function __construct(array $rawData = [])
    {
        $this->rawData = $rawData;
        $this->buildByRawData($rawData);
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function getJsonRawData(): string
    {
        return json_encode($this->rawData, JSON_UNESCAPED_UNICODE);
    }

    abstract public function buildByRawData(array $rawData): void;
}
