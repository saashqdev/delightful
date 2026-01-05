<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Parameter\User;

use Delightful\EasyDingTalk\Kernel\Exceptions\InvalidParameterException;
use Delightful\EasyDingTalk\OpenDev\Parameter\AbstractParameter;

class GetUserInfoByMobileParameter extends AbstractParameter
{
    public string $mobile;

    public function getMobile(): string
    {
        return $this->mobile;
    }

    public function setMobile(string $mobile): void
    {
        $this->mobile = $mobile;
    }

    protected function validateParams(): void
    {
        if (empty($this->mobile)) {
            throw new InvalidParameterException('mobile cannot be empty');
        }
    }
}
