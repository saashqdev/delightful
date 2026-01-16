<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskFileVersionEntity;
/** * GetFileVersionlist ResponseDTO. */

class GetFileVersionsResponseDTO extends AbstractDTO 
{
 
    protected array $list = []; 
    protected int $total = 0; 
    protected int $page = 1; 
    public function getlist (): array 
{
 return $this->list; 
}
 
    public function setlist (array $list): void 
{
 $this->list = $list; 
}
 
    public function getTotal(): int 
{
 return $this->total; 
}
 
    public function setTotal(int $total): void 
{
 $this->total = $total; 
}
 
    public function getPage(): int 
{
 return $this->page; 
}
 
    public function setPage(int $page): void 
{
 $this->page = $page; 
}
 /** * FromArrayCreateResponseDTO. * * @param TaskFileVersionEntity[] $entities Fileversion entities Array * @param int $total Total * @param int $page current Page number */ 
    public 
    static function fromData(array $entities, int $total, int $page): self 
{
 $dto = new self(); $dto->setTotal($total); $dto->setPage($page); $list = []; foreach ($entities as $entity) 
{
 $list[] = [ 'file_id' => (string) $entity->getFileId(), 'version' => $entity->getVersion(), 'edit_type' => $entity->getEditType(), 'created_at' => $entity->getCreatedAt(), ]; 
}
 $dto->setlist ($list); return $dto; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'list' => $this->list, 'total' => $this->total, 'page' => $this->page, ]; 
}
 
}
 
