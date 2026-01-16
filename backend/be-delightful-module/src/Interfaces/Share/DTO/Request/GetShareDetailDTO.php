<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Share\DTO\Request;

use App\Infrastructure\Core\AbstractDTO;
use Hyperf\HttpServer\Contract\RequestInterface;

class GetShareDetailDTO extends AbstractDTO 
{
 /** * current Page number. */ 
    public int $page = 1; /** * Per page. */ 
    public int $pageSize = 10; /** * Password. */ 
    public string $password = ''; /** * Set Page number. */ 
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
 
    public function setPassword(string $password): self 
{
 $this->password = $password; return $this; 
}
 
    public function getPassword(): string 
{
 return $this->password; 
}
 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 $dto = new self(); $dto->page = (int) $request->input('page', 1); $dto->pageSize = (int) $request->input('page_size', 10); $dto->password = $request->input('pwd', ''); return $dto; 
}
 /** * BuildValidate Rule. */ 
    public function rules(): array 
{
 return [ 'page' => 'integer|min:1', 'page_size' => 'integer|min:1|max:500', 'password' => 'nullable|string', ]; 
}
 /** * GetValidate errorMessage. */ 
    public function messages(): array 
{
 return [ 'page.integer' => 'Page numberMust beInteger', 'page.min' => 'Page numberMinimumas 1', 'page_size.integer' => 'Per pageMust beInteger', 'page_size.min' => 'Per pageMinimumas 1', 'page_size.max' => 'Per pageMaximumas 100', 'password.max' => 'CriticalMaximumLengthas 255Character', ]; 
}
 /** * PropertyName. */ 
    public function attributes(): array 
{
 return [ 'page' => 'Page number', 'page_size' => 'Per page', 'password' => 'SearchCritical', ]; 
}
 
}
 
