<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Request;

use App\Domain\Chat\DTO\Request\Common\ControlRequestData;
use App\Domain\Chat\DTO\Request\Common\DelightfulContext;

class ControlRequest extends AbstractRequest
{
    protected DelightfulContext $context;

    protected ControlRequestData $data;

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

    public function getData(): ControlRequestData
    {
        return $this->data;
    }

    public function setData(array|ControlRequestData $data): void
    {
        if ($data instanceof ControlRequestData) {
            $this->data = $data;
        } else {
            $this->data = new ControlRequestData($data);
        }
    }
}
