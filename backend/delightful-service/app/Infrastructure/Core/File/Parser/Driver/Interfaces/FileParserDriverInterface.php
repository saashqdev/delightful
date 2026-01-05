<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\File\Parser\Driver\Interfaces;

interface FileParserDriverInterface
{
    public function parse(string $filePath, string $url, string $fileExtension): string;
}
