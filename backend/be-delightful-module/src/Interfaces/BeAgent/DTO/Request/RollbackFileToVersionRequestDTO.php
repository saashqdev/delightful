<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;
/** * FileRollbackspecified VersionRequestDTO. */

class RollbackFileToVersionRequestDTO extends AbstractRequestDTO 
{
 /** * FileIDFromroute ParameterGet. */ 
    protected int $fileId = 0; /** * TargetVersion. */ 
    protected int $version = 0; 
    public function getFileId(): int 
{
 return $this->fileId; 
}
 
    public function setFileId(int|string $value): void 
{
 $this->fileId = (int) $value; 
}
 
    public function getVersion(): int 
{
 return $this->version; 
}
 
    public function setVersion(int|string $value): void 
{
 $this->version = (int) $value; 
}
 /** * GetValidate Rule. */ 
    protected 
    static function getHyperfValidate Rules(): array 
{
 return [ 'file_id' => 'required|integer|min:1', 'version' => 'required|integer|min:1', ]; 
}
 /** * GetValidate failedCustomError message. */ 
    protected 
    static function getHyperfValidate Message(): array 
{
 return [ 'file_id.required' => 'File ID cannot be empty', 'file_id.integer' => 'File ID must be an integer', 'file_id.min' => 'File ID must be greater than 0', 'version.required' => 'Version cannot be empty', 'version.integer' => 'Version must be an integer', 'version.min' => 'Version must be greater than 0', ]; 
}
 
}
 
