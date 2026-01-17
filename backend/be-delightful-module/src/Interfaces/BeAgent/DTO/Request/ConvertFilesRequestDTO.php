<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use Hyperf\HttpServer\Contract\RequestInterface;

class ConvertFilesRequestDTO
{
    /**
     * File ID list.
     */
    public array $file_ids = [];

    /**
     * Project ID.
     */
    public string $project_id = '';

    /**
     * Whether in debug mode.
     */
    public bool $is_debug = false;

    /**
     * Convert type: pdf, ppt, image.
     */
    public string $convert_type = '';

    /**
     * Other options.
     */
    public array $options = [];

    /**
     * Create DTO instance from request.
     */
    public static function fromRequest(RequestInterface $request): self
    {
        $data = $request->all();
        $dto = new self();

        $dto->file_ids = (array) ($data['file_ids'] ?? []);
        $dto->project_id = (string) ($data['project_id'] ?? '');
        $dto->is_debug = (bool) ($data['is_debug'] ?? false);
        $dto->convert_type = (string) ($data['convert_type'] ?? '');
        $dto->options = (array) ($data['options'] ?? []);

        return $dto;
    }
}
