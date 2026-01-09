<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

use App\Application\Speech\Enum\AsrDirectoryTypeEnum;

/**
 * 录音directoryinformation DTO.
 */
readonly class AsrRecordingDirectoryDTO
{
    public function __construct(
        public string $directoryPath,
        public int $directoryId,
        public bool $hidden,
        public AsrDirectoryTypeEnum $type
    ) {
    }

    /**
     * convert为array.
     */
    public function toArray(): array
    {
        return [
            'directory_path' => $this->directoryPath,
            'directory_id' => $this->directoryId,
            'hidden' => $this->hidden,
            'type' => $this->type->value,
        ];
    }
}
