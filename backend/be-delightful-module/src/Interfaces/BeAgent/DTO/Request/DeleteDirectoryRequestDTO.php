<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

class delete DirectoryRequestDTO extends AbstractRequestDTO 
{
 /** * Project ID to which the directory belongs. */ 
    public string $projectId = ''; /** * File ID of the directory to be deleted. */ 
    public string $fileId = ''; 
    public function getProjectId(): string 
{
 return $this->projectId; 
}
 
    public function getFileId(): string 
{
 return $this->fileId; 
}
 /** * Get validation rules. */ 
    protected 
    static function getHyperfValidate Rules(): array 
{
 return [ 'project_id' => 'required|string|max:50', 'file_id' => 'required|string|max:50', ]; 
}
 /** * Get custom error messages for validation failures. */ 
    protected 
    static function getHyperfValidate Message(): array 
{
 return [ 'project_id.required' => 'Project ID cannot be empty', 'project_id.string' => 'Project ID must be a string', 'project_id.max' => 'Project ID cannot exceed 50 characters', 'file_id.required' => 'File ID cannot be empty', 'file_id.string' => 'File ID must be a string', 'file_id.max' => 'File ID cannot exceed 50 characters', ]; 
}
 
}
 
