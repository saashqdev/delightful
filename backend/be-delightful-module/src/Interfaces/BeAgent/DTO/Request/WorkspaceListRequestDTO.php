<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractDTO;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\WorkspaceArchiveStatus;
use Hyperf\HttpServer\Contract\RequestInterface;

class Workspacelist RequestDTO extends AbstractDTO 
{
 /** * whether 0No 1yes . */ public ?int $isArchived = null; /** * Page number */ 
    public int $page = 1; /** * Per pageQuantity. */ 
    public int $pageSize = 10; /** * FromRequestin CreateDTO. */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 $dto = new self(); $dto->isArchived = $request->has('is_archived') ? (int) $request->input('is_archived') : WorkspaceArchiveStatus::NotArchived->value; $dto->page = (int) ($request->input('page', 1) ?: 1); $dto->pageSize = (int) ($request->input('page_size', 10) ?: 10); return $dto; 
}
 /** * Buildquery Condition. */ 
    public function buildConditions(): array 
{
 $conditions = []; if ($this->isArchived !== null) 
{
 $conditions['is_archived'] = $this->isArchived; 
}
 else 
{
 // Default $conditions['is_archived'] = WorkspaceArchiveStatus::NotArchived->value; 
}
 return $conditions; 
}
 
}
 
