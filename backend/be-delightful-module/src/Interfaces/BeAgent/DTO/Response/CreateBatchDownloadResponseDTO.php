<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

class CreateBatchDownloadResponseDTO 
{
 /** * @var string process Status (ready|processing) */ 
    protected string $status; /** * @var string Key */ 
    protected string $batchKey; /** * @var null|string DownloadURLStatusas ready */ protected ?string $downloadUrl; /** * @var int FileQuantity */ 
    protected int $fileCount; /** * @var string Descriptioninfo */ 
    protected string $message; /** * Function. */ 
    public function __construct( string $status, string $batchKey, ?string $downloadUrl = null, int $fileCount = 0, string $message = '' ) 
{
 $this->status = $status; $this->batchKey = $batchKey; $this->downloadUrl = $downloadUrl; $this->fileCount = $fileCount; $this->message = $message; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'status' => $this->status, 'batch_key' => $this->batchKey, 'download_url' => $this->downloadUrl, 'file_count' => $this->fileCount, 'message' => $this->message, ]; 
}
 /** * Getprocess Status. */ 
    public function getStatus(): string 
{
 return $this->status; 
}
 /** * GetKey. */ 
    public function getBatchKey(): string 
{
 return $this->batchKey; 
}
 /** * GetDownloadURL. */ 
    public function getDownloadUrl(): ?string 
{
 return $this->downloadUrl; 
}
 /** * GetFileQuantity. */ 
    public function getFileCount(): int 
{
 return $this->fileCount; 
}
 /** * GetDescriptioninfo . */ 
    public function getMessage(): string 
{
 return $this->message; 
}
 
}
 
