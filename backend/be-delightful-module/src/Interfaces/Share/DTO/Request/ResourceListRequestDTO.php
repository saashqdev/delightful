<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Share\DTO\Request;

use App\Infrastructure\Core\AbstractDTO;
use Hyperf\HttpServer\Contract\RequestInterface;
/** * Resourcelist RequestDTO. */

class Resourcelist RequestDTO extends AbstractDTO 
{
 /** * current Page number. */ 
    public int $page = 1; /** * Per page. */ 
    public int $pageSize = 10; /** * SearchCritical. */ 
    public string $keyword = ''; /** * ResourceType. */ public ?int $resourceType = null; /** * FromRequestin CreateDTO. */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 $dto = new self(); $dto->page = (int) $request->input('page', 1); $dto->pageSize = (int) $request->input('page_size', 10); $dto->keyword = (string) $request->input('keyword', ''); $dto->resourceType = $request->has('resource_type') ? (int) $request->input('resource_type') : null; return $dto; 
}
 /** * Set Page number. */ 
    public function setPage(int $page): self 
{
 $this->page = $page; return $this; 
}
 /** * GetPage number. */ 
    public function getPage(): int 
{
 return $this->page; 
}
 /** * Set Per page. */ 
    public function setPageSize(int $pageSize): self 
{
 $this->pageSize = $pageSize; return $this; 
}
 /** * GetPer page. */ 
    public function getPageSize(): int 
{
 return $this->pageSize; 
}
 /** * Set SearchCritical. */ 
    public function setKeyword(string $keyword): self 
{
 $this->keyword = $keyword; return $this; 
}
 /** * GetSearchCritical. */ 
    public function getKeyword(): string 
{
 return $this->keyword; 
}
 /** * Set ResourceType. */ 
    public function setResourceType(?int $resourceType): self 
{
 $this->resourceType = $resourceType; return $this; 
}
 /** * GetResourceType. */ 
    public function getResourceType(): ?int 
{
 return $this->resourceType; 
}
 /** * BuildValidate Rule. */ 
    public function rules(): array 
{
 return [ 'page' => 'integer|min:1', 'page_size' => 'integer|min:1|max:100', 'keyword' => 'nullable|string|max:255', 'resource_type' => 'nullable|integer|min:1', ]; 
}
 /** * GetValidate errorMessage. */ 
    public function messages(): array 
{
 return [ 'page.integer' => 'Page numberMust beInteger', 'page.min' => 'Page numberMinimumas 1', 'page_size.integer' => 'Per pageMust beInteger', 'page_size.min' => 'Per pageMinimumas 1', 'page_size.max' => 'Per pageMaximumas 100', 'keyword.max' => 'CriticalMaximumLengthas 255Character', 'resource_type.integer' => 'ResourceTypeMust beInteger', 'resource_type.min' => 'ResourceTypeMinimumas 1', ]; 
}
 /** * PropertyName. */ 
    public function attributes(): array 
{
 return [ 'page' => 'Page number', 'page_size' => 'Per page', 'keyword' => 'SearchCritical', 'resource_type' => 'ResourceType', ]; 
}
 
}
 
