<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

class FileUrlItemDTO extends AbstractDTO
{
    /**
     * File ID.
     */
    public string $fileId;

    /**
     * File URL.
     */
    public string $fileUrl;

    /**
     * Constructor.
     */
    public function __construct(string $fileId = '', string $fileUrl = '')
    {
        $this->fileId = $fileId;
        $this->fileUrl = $fileUrl;
    }

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->fileId = (string) ($data['file_id'] ?? '');
        $dto->fileUrl = $data['file_url'] ?? '';

        return $dto;
    }

    /**
     * Convert to array.
     * Output uses underscore naming to maintain API compatibility.
     */
    public function toArray(): array
    {
        return [
            'file_id' => $this->fileId,
            'file_url' => $this->fileUrl,
        ];
    }
}
