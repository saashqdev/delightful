<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Parameter\User;

use Dtyq\EasyDingTalk\Kernel\Exceptions\InvalidParameterException;
use Dtyq\EasyDingTalk\OpenDev\Parameter\AbstractParameter;

class GetUserInfoByCodeParameter extends AbstractParameter
{
    public string $code;

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    protected function validateParams(): void
    {
        if (empty($this->code)) {
            throw new InvalidParameterException('code cannot be empty');
        }
    }
}
