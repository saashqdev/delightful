<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

/** * FileConvertStatusResponseDTO. */

class FileConvertStatusResponseDTO 
{
 /** * ConvertStatus */ 
    protected string $status; /** * DownloadLink. */ protected ?string $downloadUrl; /** * . */ protected ?int $progress; /** * StatusMessage. */ 
    protected string $message; /** * File. */ protected ?int $totalFiles; /** * SuccessConvert. */ protected ?int $successCount; /** * ConvertType. */ protected ?string $convertType; /** * ID. */ protected ?string $batchId; /** * TaskKey. */ protected ?string $taskKey; /** * ConvertSuccess. */ protected ?float $conversionRate; /** * Function. */ 
    public function __construct( string $status, ?string $downloadUrl = null, ?int $progress = null, string $message = '', ?int $totalFiles = null, ?int $successCount = null, ?string $convertType = null, ?string $batchId = null, ?string $taskKey = null, ?float $conversionRate = null ) 
{
 $this->status = $status; $this->downloadUrl = $downloadUrl; $this->progress = $progress; $this->message = $message; $this->totalFiles = $totalFiles; $this->successCount = $successCount; $this->convertType = $convertType; $this->batchId = $batchId; $this->taskKey = $taskKey; $this->conversionRate = $conversionRate; 
}
 /** * FromArrayCreateDTO. */ 
    public 
    static function fromArray(array $data): self 
{
 return new self( $data['status'] ?? '', $data['download_url'] ?? null, $data['progress'] ?? null, $data['message'] ?? '', $data['total_files'] ?? null, $data['success_count'] ?? null, $data['convert_type'] ?? null, $data['batch_id'] ?? null, $data['task_key'] ?? null, $data['conversion_rate'] ?? null ); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'status' => $this->status, 'download_url' => $this->downloadUrl, 'progress' => $this->progress, 'message' => $this->message, 'total_files' => $this->totalFiles, 'success_count' => $this->successCount, 'convert_type' => $this->convertType, 'batch_id' => $this->batchId, 'task_key' => $this->taskKey, 'conversion_rate' => $this->conversionRate, ]; 
}
 /** * GetConvertStatus */ 
    public function getStatus(): string 
{
 return $this->status; 
}
 /** * Set ConvertStatus */ 
    public function setStatus(string $status): self 
{
 $this->status = $status; return $this; 
}
 /** * GetDownloadLink. */ 
    public function getDownloadUrl(): ?string 
{
 return $this->downloadUrl; 
}
 /** * Set DownloadLink. */ 
    public function setDownloadUrl(?string $downloadUrl): self 
{
 $this->downloadUrl = $downloadUrl; return $this; 
}
 /** * Get. */ 
    public function getProgress(): ?int 
{
 return $this->progress; 
}
 /** * Set . */ 
    public function setProgress(?int $progress): self 
{
 $this->progress = $progress; return $this; 
}
 /** * GetStatusMessage. */ 
    public function getMessage(): string 
{
 return $this->message; 
}
 /** * Set StatusMessage. */ 
    public function setMessage(string $message): self 
{
 $this->message = $message; return $this; 
}
 /** * GetFile. */ 
    public function getTotalFiles(): ?int 
{
 return $this->totalFiles; 
}
 /** * Set File. */ 
    public function setTotalFiles(?int $totalFiles): self 
{
 $this->totalFiles = $totalFiles; return $this; 
}
 /** * GetSuccessConvert. */ 
    public function getSuccessCount(): ?int 
{
 return $this->successCount; 
}
 /** * Set SuccessConvert. */ 
    public function setSuccessCount(?int $successCount): self 
{
 $this->successCount = $successCount; return $this; 
}
 /** * GetConvertType. */ 
    public function getConvertType(): ?string 
{
 return $this->convertType; 
}
 /** * Set ConvertType. */ 
    public function setConvertType(?string $convertType): self 
{
 $this->convertType = $convertType; return $this; 
}
 /** * GetID. */ 
    public function getBatchId(): ?string 
{
 return $this->batchId; 
}
 /** * Set ID. */ 
    public function setBatchId(?string $batchId): self 
{
 $this->batchId = $batchId; return $this; 
}
 /** * GetTaskKey. */ 
    public function getTaskKey(): ?string 
{
 return $this->taskKey; 
}
 /** * Set TaskKey. */ 
    public function setTaskKey(?string $taskKey): self 
{
 $this->taskKey = $taskKey; return $this; 
}
 /** * GetConvertSuccess. */ 
    public function getConversionRate(): ?float 
{
 return $this->conversionRate; 
}
 /** * Set ConvertSuccess. */ 
    public function setConversionRate(?float $conversionRate): self 
{
 $this->conversionRate = $conversionRate; return $this; 
}
 
}
 
