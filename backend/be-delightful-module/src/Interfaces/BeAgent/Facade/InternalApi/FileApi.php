<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\Facade\InternalApi;

use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\BeAgent\Service\FileVersionAppService;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\CreateFileVersionRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\Facade\AbstractApi;
use Hyperf\HttpServer\Contract\RequestInterface;

#[ApiResponse('low_code')]
class FileApi extends AbstractApi
{
    public function __construct(
        private readonly FileVersionAppService $fileVersionAppService,
        protected RequestInterface $request,
    ) {
        parent::__construct($request);
    }

    /**
     * 创建文件版本.
     *
     * @return array 创建结果
     */
    public function createFileVersion(): array
    {
        $requestDTO = CreateFileVersionRequestDTO::fromRequest($this->request);

        $responseDTO = $this->fileVersionAppService->createFileVersion($requestDTO);

        return $responseDTO->toArray();
    }
}
