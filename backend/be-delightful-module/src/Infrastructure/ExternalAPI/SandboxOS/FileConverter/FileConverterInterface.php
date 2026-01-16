<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter\Request\FileConverterRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter\Response\FileConverterResponse;

interface FileConverterInterface 
{
 /** * ConvertFile. */ 
    public function convert(string $userId, string $organizationCode, string $sandboxId, string $projectId, FileConverterRequest $request, string $workDir): FileConverterResponse; /** * query ConvertResult. * * @param string $sandboxId Sandbox ID * @param string $projectId Project ID * @param string $taskKey Taskkey * @return FileConverterResponse ConvertResult */ 
    public function queryConvertResult(string $sandboxId, string $projectId, string $taskKey): FileConverterResponse; 
}
 
