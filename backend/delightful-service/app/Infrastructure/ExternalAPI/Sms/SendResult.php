<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

/**
 * 所有短信驱动的return结果必须转换为此object
 */
class SendResult
{
    private int $code;

    private string $errorMsg;

    public function setResult(int $code, string $msg): SendResult
    {
        $this->errorMsg = $msg;
        $this->code = $code;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'errorMsg' => $this->errorMsg,
        ];
    }
}
