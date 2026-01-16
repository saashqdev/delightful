<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Config;

/** * ASR FileConfiguration * for finishTask InterfaceFileprocess Configuration. */ readonly

class AsrNoteFileConfig 
{
 /** * @param string $sourcePath FilePathFor workspace * @param string $targetPath TargetFilePathFor workspace * @param string $action Typerename_and_move */ 
    public function __construct( 
    private string $sourcePath, 
    private string $targetPath, 
    private string $action = 'rename_and_move' ) 
{
 
}
 
    public function getSourcePath(): string 
{
 return $this->sourcePath; 
}
 
    public function getTargetPath(): string 
{
 return $this->targetPath; 
}
 
    public function getAction(): string 
{
 return $this->action; 
}
 /** * Convert toArrayfor HTTP Request. */ 
    public function toArray(): array 
{
 return [ 'source_path' => $this->sourcePath, 'target_path' => $this->targetPath, 'action' => $this->action, ]; 
}
 
}
 
