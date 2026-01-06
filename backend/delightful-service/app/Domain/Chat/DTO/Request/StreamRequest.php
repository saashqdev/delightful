<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Request;

use App\Domain\Chat\DTO\Request\Common\ChatRequestData;
use App\Domain\Chat\DTO\Request\Common\DelightfulContext;

class StreamRequest extends AbstractRequest
{
    protected DelightfulContext $context;

    protected ChatRequestData $data;

    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    public function getContext(): DelightfulContext
    {
        return $this->context;
    }

    public function setContext(array|DelightfulContext $context): void
    {
        if ($context instanceof DelightfulContext) {
            $this->context = $context;
        } else {
            $this->context = new DelightfulContext($context);
        }
    }

    public function getData(): ChatRequestData
    {
        return $this->data;
    }

    public function setData(array|ChatRequestData $data): void
    {
        if ($data instanceof ChatRequestData) {
            $this->data = $data;
        } else {
            $this->data = new ChatRequestData($data);
        }
    }
}
