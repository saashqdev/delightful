<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

/** * sandbox FileSaveRequestClass * for call sandbox /api/v1/files/edit Interface. */

class SaveFilesRequest 
{
 
    private array $files; 
    public function __construct(array $files) 
{
 $this->files = $files; 
}
 /** * CreateFileSaveRequest */ 
    public 
    static function create(array $files): self 
{
 return new self($files); 
}
 /** * Fromapplication layer DataCreateRequest */ 
    public 
    static function fromFileData(array $fileDatalist ): self 
{
 $files = []; foreach ($fileDatalist as $fileData) 
{
 $files[] = [ 'file_key' => $fileData['file_key'], 'file_path' => $fileData['file_path'], 'content' => $fileData['content'], 'is_encrypted' => false, ]; 
}
 return new self($files); 
}
 /** * Convert toArrayFormatfor APIcall . */ 
    public function toArray(): array 
{
 return [ 'files' => $this->files, ]; 
}
 /** * GetFilelist . */ 
    public function getFiles(): array 
{
 return $this->files; 
}
 /** * GetFileQuantity. */ 
    public function getFileCount(): int 
{
 return count($this->files); 
}
 
}
 
