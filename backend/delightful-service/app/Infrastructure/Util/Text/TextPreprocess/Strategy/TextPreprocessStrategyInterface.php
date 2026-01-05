<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Text\TextPreprocess\Strategy;

interface TextPreprocessStrategyInterface
{
    public function preprocess(string $content): string;
}
