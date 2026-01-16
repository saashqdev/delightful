<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Config;

/** * ASR Configuration * for finishTask InterfaceMergeConfiguration. */ readonly

class AsrAudioConfig 
{
 /** * @param string $sourceDir DirectoryFor workspace * @param string $targetDir TargetDirectoryFor workspace * @param string $outputFilename OutputFileExtension */ 
    public function __construct( 
    private string $sourceDir, 
    private string $targetDir, 
    private string $outputFilename ) 
{
 
}
 
    public function getSourceDir(): string 
{
 return $this->sourceDir; 
}
 
    public function getTargetDir(): string 
{
 return $this->targetDir; 
}
 
    public function getOutputFilename(): string 
{
 return $this->outputFilename; 
}
 /** * Convert toArrayfor HTTP Request. */ 
    public function toArray(): array 
{
 return [ 'source_dir' => $this->sourceDir, 'target_dir' => $this->targetDir, 'output_filename' => $this->outputFilename, ]; 
}
 
}
 
