<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Config\AsrAudioConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Config\AsrNoteFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Config\AsrTranscriptFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Response\Asrrecord erResponse;
/** * ASR ServiceInterface. */

interface Asrrecord erInterface 
{
 /** * ASR Task * Pairsandbox POST /api/asr/task/start. * * @param string $sandboxId Sandbox ID * @param string $taskKey TaskKey * @param string $sourceDir DirectoryRelativePath * @param string $workspaceDir workspace DirectoryDefault .workspace * @param null|AsrNoteFileConfig $noteFileConfig FileConfigurationObjectOptional * @param null|AsrTranscriptFileConfig $transcriptFileConfig FluidIdentifyConfigurationObjectOptional * @return Asrrecord erResponse ResponseResult */ 
    public function startTask( string $sandboxId, string $taskKey, string $sourceDir, string $workspaceDir = '.workspace', ?AsrNoteFileConfig $noteFileConfig = null, ?AsrTranscriptFileConfig $transcriptFileConfig = null ): Asrrecord erResponse; /** * complete ASR TaskMerge (V2 StructureVersion) * Pairsandbox POST /api/asr/task/finish * Supportquery StatusMultiple callsSameParameter. * * @param string $sandboxId Sandbox ID * @param string $taskKey TaskKey * @param string $workspaceDir workspace Directory * @param AsrAudioConfig $audioConfig ConfigurationObject * @param null|AsrNoteFileConfig $noteFileConfig FileConfigurationObject * @param null|AsrTranscriptFileConfig $transcriptFileConfig FluidIdentifyConfigurationObject * @return Asrrecord erResponse ResponseResult */ 
    public function finishTask( string $sandboxId, string $taskKey, string $workspaceDir, AsrAudioConfig $audioConfig, ?AsrNoteFileConfig $noteFileConfig = null, ?AsrTranscriptFileConfig $transcriptFileConfig = null ): Asrrecord erResponse; /** * cancel ASR Task * Pairsandbox POST /api/asr/task/cancel. * * @param string $sandboxId Sandbox ID * @param string $taskKey TaskKey * @param string $workspaceDir workspace DirectoryDefault .workspace * @return Asrrecord erResponse ResponseResult */ 
    public function cancelTask( string $sandboxId, string $taskKey, string $workspaceDir = '.workspace' ): Asrrecord erResponse; 
}
 
