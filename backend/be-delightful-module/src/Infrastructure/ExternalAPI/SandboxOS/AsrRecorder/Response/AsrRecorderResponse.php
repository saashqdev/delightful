<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Response;

/** * ASR ServiceResponse. */

class Asrrecord erResponse 
{
 
    public int $code 
{
 get 
{
 return $this->code; 
}
 
}
 
    public string $message 
{
 get 
{
 return $this->message; 
}
 
}
 
    private array $data 
{
 get 
{
 return $this->data; 
}
 
}
 
    public function __construct(int $code, string $message, array $data) 
{
 $this->code = $code; $this->message = $message; $this->data = $data; 
}
 /** * Fromsandbox ResultCreateResponse. */ 
    public 
    static function fromGatewayResult(mixed $result): self 
{
 if (! $result->isSuccess()) 
{
 return new self( $result->getCode(), $result->getMessage(), [] ); 
}
 $data = $result->getData(); return new self( $result->getCode(), $result->getMessage(), $data ); 
}
 /** * From API ResponseCreate. */ 
    public 
    static function fromApiResponse(array $response): self 
{
 return new self( $response['code'] ?? -1, $response['message'] ?? '', $response['data'] ?? [] ); 
}
 /** * whether Successcode = 1000. */ 
    public function isSuccess(): bool 
{
 return $this->code === 1000; 
}
 /** * GetTaskStatus. */ 
    public function getStatus(): string 
{
 return $this->data['status'] ?? 'error'; 
}
 /** * GetFilePath (Compatible V2 OldFormat). */ 
    public function getFilePath(): ?string 
{
 // V2 FormatFrom files.audio_file.path if (isset($this->data['files']['audio_file']['path'])) 
{
 return $this->data['files']['audio_file']['path']; 
}
 // OldFormatFrom file_path Compatible $path = $this->data['file_path'] ?? null; return $path !== '' ? $path : null; 
}
 /** * Getseconds  (Compatible V2 OldFormat). */ 
    public function getDuration(): ?int 
{
 // V2 FormatFrom files.audio_file.duration if (isset($this->data['files']['audio_file']['duration'])) 
{
 return (int) $this->data['files']['audio_file']['duration']; 
}
 // OldFormatFrom duration Compatible return $this->data['duration'] ?? null; 
}
 /** * GetFileSize (Compatible V2 OldFormat). */ 
    public function getFileSize(): ?int 
{
 // V2 FormatFrom files.audio_file.size if (isset($this->data['files']['audio_file']['size'])) 
{
 return (int) $this->data['files']['audio_file']['size']; 
}
 // OldFormatFrom file_size Compatible return $this->data['file_size'] ?? null; 
}
 /** * GetError message. */ 
    public function getErrorMessage(): ?string 
{
 return $this->data['error_message'] ?? null; 
}
 /** * Getcomplete data Arrayfor Responseprocess . */ 
    public function getData(): array 
{
 return $this->data; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'code' => $this->code, 'message' => $this->message, 'data' => $this->data, ]; 
}
 
}
 
