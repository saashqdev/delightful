<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Hyperf\HttpServer\Contract\RequestInterface;

class GetFileUrlsRequestDTO 
{
 /** * list of file IDs. */ 
    private array $fileIds; 
    private string $token; 
    private string $downloadMode; 
    private string $topicId; 
    private string $projectId; /** * Cache setting, default is true. */ 
    private bool $cache; /** * FileVersionMapFormat[file_id => version_number] * IfFilespecified VersionUsingcurrent Version. */ 
    private array $fileVersions; /** * Constructor. */ 
    public function __construct(array $params) 
{
 $this->fileIds = $params['file_ids'] ?? []; $this->token = $params['token'] ?? ''; $this->downloadMode = $params['download_mode'] ?? 'preview'; $this->topicId = $params['topic_id'] ?? ''; $this->projectId = $params['project_id'] ?? ''; $this->cache = $params['cache'] ?? true; $this->fileVersions = $params['file_versions'] ?? []; $this->validate(); 
}
 /** * FromHTTPRequestCreateDTO. */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 return new self($request->all()); 
}
 /** * GetFileIDlist . */ 
    public function getFileIds(): array 
{
 return $this->fileIds; 
}
 
    public function getToken(): string 
{
 return $this->token; 
}
 
    public function getDownloadMode(): string 
{
 return $this->downloadMode; 
}
 
    public function getTopicId(): string 
{
 return $this->topicId; 
}
 
    public function getProjectId(): string 
{
 return $this->projectId; 
}
 
    public function getCache(): bool 
{
 return $this->cache; 
}
 
    public function setProjectId(string $projectId) 
{
 $this->projectId = $projectId; 
}
 /** * GetFileVersionMap. */ 
    public function getFileVersions(): array 
{
 return $this->fileVersions; 
}
 /** * Set FileVersionMap. */ 
    public function setFileVersions(array $fileVersions): void 
{
 $this->fileVersions = $fileVersions; 
}
 /** * Getspecified FileVersion. * * @param int $fileId FileID * @return null|int Versionspecified Return null */ 
    public function getFileVersion(int $fileId): ?int 
{
 return $this->fileVersions[$fileId] ?? null; 
}
 /** * Validate RequestData. * * @throws BusinessException IfValidate failedThrowException */ /* @phpstan-ignore-next-line */ 
    private function validate(): void 
{
 if (empty($this->fileIds)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'file_ids.required'); 
}
 if (empty($this->projectId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'project_id.required'); 
}
 // Validate FileVersionFormat if (! empty($this->fileVersions)) 
{
 foreach ($this->fileVersions as $fileId => $version) 
{
 if (! is_numeric($fileId) || ! is_numeric($version) || (int) $version < 1) 
{
 ExceptionBuilder::throw( GenericErrorCode::ParameterValidate Failed, 'file_versions.invalid_format' ); 
}
 
}
 
}
 
}
 
}
 
