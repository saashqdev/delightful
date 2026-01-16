<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractDTO;

class GetWorkspacesRequestDTO extends AbstractDTO 
{
 /** * Page number */ 
    private int $page = 1; /** * Per pageQuantity. */ 
    private int $pageSize = 10; 
    public function __construct(array $params = []) 
{
 $this->page = (int) ($params['page'] ?? 1); $this->pageSize = (int) ($params['page_size'] ?? 10); 
}
 
    public function getPage(): int 
{
 return $this->page; 
}
 
    public function getPageSize(): int 
{
 return $this->pageSize; 
}
 
}
 
