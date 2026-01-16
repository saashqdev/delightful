<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\Application\SuperAgent\Config\Batchprocess Config;
use JsonSerializable;
/** * Batch Save File Content Request DTO. */

class BatchSaveFileContentRequestDTO implements JsonSerializable 
{
 /** * Array of SaveFileContentRequestDTO objects. * * @var SaveFileContentRequestDTO[] */ 
    private array $files = []; /** * Original count before deduplication. */ 
    private int $originalCount = 0; /** * @param SaveFileContentRequestDTO[] $files */ 
    public function __construct(array $files = []) 
{
 $this->files = $files; 
}
 /** * Create DTO from request data. */ 
    public 
    static function fromRequest(array $requestData): self 
{
 if (empty($requestData)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'files_array_required'); 
}
 $files = []; $fileMap = []; // Map table for deduplication foreach ($requestData as $fileData) 
{
 if (! is_array($fileData)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterValidate Failed, 'invalid_file_data_format'); 
}
 $fileDTO = SaveFileContentRequestDTO::fromRequest($fileData); $fileId = $fileDTO->getFileId(); // Iffile_idDuplicateFinally $fileMap[$fileId] = $fileDTO; 
}
 // Convert toArraydeduplication Filelist  $files = array_values($fileMap); $dto = new self($files); $dto->originalCount = count($requestData); // record Original quantity $dto->validate(); return $dto; 
}
 /** * Validate request parameters. */ 
    public function validate(): void 
{
 if (empty($this->files)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'files_array_required'); 
}
 $maxBatchSize = Batchprocess Config::getBatchSizeLimit(); if (count($this->files) > $maxBatchSize) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterValidate Failed, 'batch_size_exceeded'); 
}
 // Validate each file foreach ($this->files as $file) 
{
 $file->validate(); 
}
 // AttentionDuplicatefile_idAtfromRequestMethodin automatic deduplication check 
}
 /** * @return SaveFileContentRequestDTO[] */ 
    public function getFiles(): array 
{
 return $this->files; 
}
 /** * @param SaveFileContentRequestDTO[] $files */ 
    public function setFiles(array $files): void 
{
 $this->files = $files; 
}
 
    public function getFileCount(): int 
{
 return count($this->files); 
}
 
    public function getOriginalCount(): int 
{
 return $this->originalCount; 
}
 
    public function getDeduplicatedCount(): int 
{
 return $this->originalCount - count($this->files); 
}
 
    public function jsonSerialize(): array 
{
 return array_map(fn ($file) => $file->jsonSerialize(), $this->files); 
}
 
}
 
