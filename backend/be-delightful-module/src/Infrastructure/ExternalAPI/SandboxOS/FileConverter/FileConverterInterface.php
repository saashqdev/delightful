<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter\Request\FileConverterRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter\Response\FileConverterResponse;

interface FileConverterInterface
{
    /**
        * Convert files.
     */
    public function convert(string $userId, string $organizationCode, string $sandboxId, string $projectId, FileConverterRequest $request, string $workDir): FileConverterResponse;

    /**
     * Query conversion result.
     *
     * @param string $sandboxId Sandbox ID
     * @param string $projectId Project ID
     * @param string $taskKey Task key
     * @return FileConverterResponse Conversion result
     */
    public function queryConvertResult(string $sandboxId, string $projectId, string $taskKey): FileConverterResponse;
}
