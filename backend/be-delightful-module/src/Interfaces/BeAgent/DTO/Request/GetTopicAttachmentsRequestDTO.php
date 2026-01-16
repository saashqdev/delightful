<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use Hyperf\HttpServer\Contract\RequestInterface;

class GetTopicAttachmentsRequestDTO 
{
 
    protected string $topicId; /** * Loginuser Using token Getinfo . */ 
    protected string $token; /** * current Page number. */ 
    protected int $page; /** * Per pageQuantity. */ 
    protected int $pageSize; /** * FileTypeFilter. */ 
    protected array $fileType = []; 
    public function __construct(array $data = [], ?string $topicId = null) 
{
 // Using topicId Parameter $this->topicId = $topicId ?? (string) ($data['topic_id'] ?? ''); $this->page = (int) ($data['page'] ?? 1); $this->pageSize = (int) ($data['page_size'] ?? 200); $this->token = (string) ($data['token'] ?? ''); // process FileTypeCanReceiveStringor Array if (isset($data['file_type'])) 
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
 
    public function getTopicId(): string 
{
 return $this->topicId; 
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
 
    public function getToken(): string 
{
 return $this->token; 
}
 /** * Set topic ID. * * @param string $topicId topic ID * @return self Return current InstanceSupportcall */ 
    public function setTopicId(string $topicId): self 
{
 $this->topicId = $topicId; return $this; 
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
 /** * Set Access token. * * @param string $token Access token * @return self Return current InstanceSupportcall */ 
    public function setToken(string $token): self 
{
 $this->token = $token; return $this; 
}
 
}
 
