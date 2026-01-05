<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\Utils;

class AesUtil
{
    public static function encode(string $key, string $str): string
    {
        return openssl_encrypt($str, 'AES-256-ECB', $key);
    }

    public static function decode(string $key, string $str): string
    {
        return openssl_decrypt($str, 'AES-256-ECB', $key);
    }
}
