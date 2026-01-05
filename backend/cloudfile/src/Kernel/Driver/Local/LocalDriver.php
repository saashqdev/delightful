<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Driver\Local;

use InvalidArgumentException;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalDriver extends LocalFilesystemAdapter implements FilesystemAdapter
{
    public function __construct(array $config)
    {
        $root = $config['root'] ?? '';
        if (empty($root)) {
            throw new InvalidArgumentException('Local filesystem root path is required');
        }

        parent::__construct($root);
    }
}
