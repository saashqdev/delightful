<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

class check BatchDownloadResponseDTO 
{
 /** * @var string process Status (ready|processing|failed) */ 
    protected string $status; /** * @var null|string DownloadURLStatusas ready */ protected ?string $downloadUrl; /** * @var null|int process 0-100 */ protected ?int $progress; /** * @var string Descriptioninfo */ 
    protected string $message; /** * Function. */ 
    public function __construct( string $status, ?string $downloadUrl = null, ?int $progress = null, string $message = '' ) 
{
 $this->status = $status; $this->downloadUrl = $downloadUrl; $this->progress = $progress; $this->message = $message; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'status' => $this->status, 'download_url' => $this->downloadUrl, 'progress' => $this->progress, 'message' => $this->message, ]; 
}
 /** * Getprocess Status. */ 
    public function getStatus(): string 
{
 return $this->status; 
}
 /** * GetDownloadURL. */ 
    public function getDownloadUrl(): ?string 
{
 return $this->downloadUrl; 
}
 /** * Getprocess . */ 
    public function getProgress(): ?int 
{
 return $this->progress; 
}
 /** * GetDescriptioninfo . */ 
    public function getMessage(): string 
{
 return $this->message; 
}
 
}
 
