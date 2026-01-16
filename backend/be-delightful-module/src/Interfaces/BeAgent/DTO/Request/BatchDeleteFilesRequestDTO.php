<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

class Batchdelete FilesRequestDTO extends AbstractRequestDTO 
{
 /** * Array of file IDs to be deleted. */ 
    public array $fileIds = []; /** * Project ID to which the files belong. */ 
    public string $projectId = ''; /** * whether to force delete (optional, default false). */ 
    public bool $forcedelete = false; 
    public function getFileIds(): array 
{
 return $this->fileIds; 
}
 
    public function getProjectId(): string 
{
 return $this->projectId; 
}
 
    public function getForcedelete (): bool 
{
 return $this->forcedelete ; 
}
 /** * Get validation rules. */ 
    protected 
    static function getHyperfValidate Rules(): array 
{
 return [ 'file_ids' => 'required|array|min:1|max:500', 'file_ids.*' => 'required|integer|min:1', 'project_id' => 'required|string|max:50', 'force_delete' => 'nullable|boolean', ]; 
}
 /** * Get custom error messages for validation failures. */ 
    protected 
    static function getHyperfValidate Message(): array 
{
 return [ 'file_ids.required' => 'File IDs cannot be empty', 'file_ids.array' => 'File IDs must be an array', 'file_ids.min' => 'At least one file ID is required', 'file_ids.max' => 'Cannot delete more than 500 files at once', 'file_ids.*.required' => 'each file ID cannot be empty', 'file_ids.*.integer' => 'each file ID must be an integer', 'file_ids.*.min' => 'each file ID must be greater than 0', 'project_id.required' => 'Project ID cannot be empty', 'project_id.string' => 'Project ID must be a string', 'project_id.max' => 'Project ID cannot exceed 50 characters', 'force_delete.boolean' => 'Force delete must be a boolean value', ]; 
}
 
}
 
