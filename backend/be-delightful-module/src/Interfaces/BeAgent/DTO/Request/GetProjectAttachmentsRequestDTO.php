<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use Hyperf\HttpServer\Contract\RequestInterface;

class GetProjectAttachmentsRequestDTO 
{
 
    protected string $projectId; /** * current Page number. */ 
    protected int $page; /** * Per pageQuantity. */ 
    protected int $pageSize; /** * FileTypeFilter. */ 
    protected array $fileType = []; /** * Access tokenfor LoginSchema. */ protected ?string $token = null; 
    public function __construct(array $data = [], ?string $projectId = null) 
{
 // Using projectId Parameter $this->projectId = $projectId ?? (string) ($data['project_id'] ?? ''); $this->page = (int) ($data['page'] ?? 1); $this->pageSize = (int) ($data['page_size'] ?? 200); $this->token = $data['token'] ?? null; // process FileTypeCanReceiveStringor Array if (isset($data['file_type'])) 
{
 if (is_array($data['file_type'])) 
{
 $this->fileType = $data['file_type']; 
}
 elseif (is_string($data['file_type']) && ! empty($data['file_type'])) 
{
 $this->fileType = [$data['file_type']]; 
}
 
}
 
}
 /** * FromRequestCreate DTO. * * @param RequestInterface $request RequestObject * @return self Return New DTO Instance */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 return new self( $request->all(), $request->route('id') ); 
}
 
    public function getProjectId(): string 
{
 return $this->projectId; 
}
 
    public function getPage(): int 
{
 return $this->page; 
}
 
    public function getPageSize(): int 
{
 return $this->pageSize; 
}
 
    public function getFileType(): array 
{
 return $this->fileType; 
}
 /** * Set Project ID. * * @param string $projectId Project ID * @return self Return current InstanceSupportcall */ 
    public function setProjectId(string $projectId): self 
{
 $this->projectId = $projectId; return $this; 
}
 /** * Set current Page number. * * @param int $page current Page number * @return self Return current InstanceSupportcall */ 
    public function setPage(int $page): self 
{
 $this->page = $page; return $this; 
}
 /** * Set Per pageQuantity. * * @param int $pageSize Per pageQuantity * @return self Return current InstanceSupportcall */ 
    public function setPageSize(int $pageSize): self 
{
 $this->pageSize = $pageSize; return $this; 
}
 /** * Set FileTypeFilter. * * @param array $fileType FileTypeFilterArray * @return self Return current InstanceSupportcall */ 
    public function setFileType(array $fileType): self 
{
 $this->fileType = $fileType; return $this; 
}
 /** * GetAccess token. * * @return null|string Access token */ 
    public function getToken(): ?string 
{
 return $this->token; 
}
 /** * Set Access token. * * @param null|string $token Access token * @return self Return current InstanceSupportcall */ 
    public function setToken(?string $token): self 
{
 $this->token = $token; return $this; 
}
 
}
 
