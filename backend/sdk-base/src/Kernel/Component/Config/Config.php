<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\SdkBase\Kernel\Component\Config;

use Adbar\Dot;
use InvalidArgumentException;

class Config extends Dot
{
    public function __construct(array $items = [])
    {
        parent::__construct($items);

        // Check some required fields
        if (empty($this->getSdkName())) {
            throw new InvalidArgumentException('Missing Config: sdk_name');
        }
    }

    public function getSdkName(): string
    {
        return $this->get('sdk_name', '');
    }

    public function getRequestTimeout(): int
    {
        return (int) $this->get('request_timeout', 30);
    }
}
