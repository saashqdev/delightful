<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Response;

/** * sandbox FileSaveResponseClass * Parse sandbox /api/v1/files/edit InterfaceReturn Data. */

class SaveFilesResponse 
{
 
    private array $editSummary; 
    private array $results; 
    public function __construct(array $editSummary, array $results) 
{
 $this->editSummary = $editSummary; $this->results = $results; 
}
 /** * FromAPIResponseDataCreateResponseObject */ 
    public 
    static function fromApiResponse(array $data): self 
{
 return new self( $data['edit_summary'] ?? [], $data['results'] ?? [] ); 
}
 /** * GetEdit */ 
    public function getEditSummary(): array 
{
 return $this->editSummary; 
}
 /** * GetResultlist . */ 
    public function getResults(): array 
{
 return $this->results; 
}
 /** * check whether AllFileSuccess */ 
    public function isAllSuccess(): bool 
{
 return $this->editSummary['all_success'] ?? false; 
}
 /** * check whether AllFileUploadSuccess */ 
    public function isAllUploaded(): bool 
{
 return $this->editSummary['all_uploaded'] ?? false; 
}
 /** * GetSuccessQuantity. */ 
    public function getSuccessCount(): int 
{
 return $this->editSummary['success_count'] ?? 0; 
}
 /** * GetFailedQuantity. */ 
    public function getFailedCount(): int 
{
 return $this->editSummary['failed_count'] ?? 0; 
}
 /** * GetQuantity. */ 
    public function getTotalCount(): int 
{
 return $this->editSummary['total_count'] ?? 0; 
}
 /** * GetUploadSuccessQuantity. */ 
    public function getUploadSuccessCount(): int 
{
 return $this->editSummary['upload_success_count'] ?? 0; 
}
 /** * Convert toArrayFormatInterfaceCompatible. */ 
    public function toArray(): array 
{
 return [ 'edit_summary' => $this->editSummary, 'results' => $this->results, ]; 
}
 /** * GetFailedFilelist . */ 
    public function getFailedFiles(): array 
{
 return array_filter($this->results, function ($result) 
{
 return ! ($result['success'] ?? true); 
}
); 
}
 /** * GetSuccessFilelist . */ 
    public function getSuccessFiles(): array 
{
 return array_filter($this->results, function ($result) 
{
 return $result['success'] ?? true; 
}
); 
}
 
}
 
