<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Text\TextPreprocess\Strategy;

class ReplaceWhitespaceTextPreprocessStrategy extends AbstractTextPreprocessStrategy
{
    public function preprocess(string $content): string
    {
        // 替换连续的null白character(换line符、制table符、null格)
        return preg_replace('/[\s\n\t]+/', '', $content);
    }
}
