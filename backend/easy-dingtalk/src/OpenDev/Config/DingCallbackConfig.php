<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Config;

class DingCallbackConfig
{
    private string $token;

    private string $aesKey;

    public function __construct(array $callbackConfig)
    {
        $this->token = $callbackConfig['token'] ?? '';
        $this->aesKey = $callbackConfig['aes_key'] ?? '';
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getAesKey(): string
    {
        return $this->aesKey;
    }
}
