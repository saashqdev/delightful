<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Endpoint\DingCallback;

class SHA1
{
    public function getSHA1(string $token, string $timestamp, string $nonce, string $encrypt_msg): string
    {
        $array = [$encrypt_msg, $token, $timestamp, $nonce];
        sort($array, SORT_STRING);
        $str = implode($array);
        return sha1($str);
    }
}
