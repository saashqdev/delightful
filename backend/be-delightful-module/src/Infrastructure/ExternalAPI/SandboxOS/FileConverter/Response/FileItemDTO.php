<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter\Response;

/**
 * 转换结果中的文件项.
 */
class FileItemDTO
{
    public string $filename;

    public string $localPath;

    public string $type;

    public string $ossKey;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->filename = $data['filename'] ?? '';
        $dto->localPath = $data['local_path'] ?? '';
        $dto->type = $data['type'] ?? '';
        $dto->ossKey = $data['oss_key'] ?? '';
        return $dto;
    }

    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'local_path' => $this->localPath,
            'type' => $this->type,
            'oss_key' => $this->ossKey,
        ];
    }
}
