<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Config;

/** * ASR FluidIdentifyFileConfiguration * for finishTask InterfaceFluidIdentifyFileprocess Configuration. */ readonly

class AsrTranscriptFileConfig 
{
 /** * @param string $sourcePath FilePathFor workspace * @param string $action Typedelete - directly delete  */ 
    public function __construct( 
    private string $sourcePath, 
    private string $action = 'delete' ) 
{
 
}
 
    public function getSourcePath(): string 
{
 return $this->sourcePath; 
}
 
    public function getAction(): string 
{
 return $this->action; 
}
 /** * Convert toArrayfor HTTP Request. */ 
    public function toArray(): array 
{
 return [ 'source_path' => $this->sourcePath, 'action' => $this->action, ]; 
}
 
}
 
