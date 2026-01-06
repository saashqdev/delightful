<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

class FileNameResponseDTO
{
    public function __construct(
        public readonly string $fileName
    ) {
    }

    public function toArray(): array
    {
        return [
            'file_name' => $this->fileName,
        ];
    }
}
